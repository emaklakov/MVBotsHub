<?php
// app/Jobs/ProcessTelegramUpdate.php

namespace App\Jobs\Telegram;

use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Enums\SubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Conversations\Models\Message as MessageModel;
use App\Domain\Conversations\Services\PhoneMergeService;
use App\Domain\Flows\Enums\FlowStatus;
use App\Domain\Flows\Enums\FlowVersionStatus;
use App\Domain\Flows\Enums\TriggerTypes;
use App\Domain\Flows\Models\Flow;
use App\Domain\Flows\Services\FlowRunner;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\DTO\Contact;
use DefStudio\Telegraph\DTO\Message as TelegramMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use ReflectionMethod;

class ProcessTelegramUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    /** Явный белый список команд, которые можно вызвать через /command (см. пункт 2 выше). */
    private const ALLOWED_COMMANDS = ['start'];

    public function __construct(
        public Bot $bot,
        public array $update,
    ) {}

    public function handle(): void
    {
        $updateId = $this->update['update_id'];

        $inserted = DB::table('processed_updates')->insertOrIgnore([
            'bot_id' => $this->bot->id,
            'update_id' => $updateId,
            'processed_at' => now(),
        ]);

        if ($inserted === 0) {
            return; // Telegram уже присылал этот update — не обрабатываем повторно
        }

        try {
            $this->dispatchUpdate();
        } catch (\Throwable $e) {
            LogService::logError('Ошибка обработки Telegram update', [
                'bot_id' => $this->bot->id,
                'update_id' => $updateId,
                'message' => $e->getMessage(),
            ]);

            throw $e; // пусть Job ретраит/уйдёт в failed() штатным механизмом очереди
        }
    }

    //---- Диспетчеризация (аналог WebhookHandler::handle()) -----------------

    protected function dispatchUpdate(): void
    {
        match (true) {
            isset($this->update['message']) => $this->handleMessage(
                TelegramMessage::fromArray($this->update['message'])
            ),
            isset($this->update['callback_query']) => $this->handleCallbackQuery(
                CallbackQuery::fromArray($this->update['callback_query'])
            ),
            default => $this->handleUnsupportedUpdate(),
        };
    }

    /**
     * Заглушка под остальные типы апдейтов (edited_message, my_chat_member и т.д.)
     */
    protected function handleUnsupportedUpdate(): void
    {
        // .. пока ничего не делаем
    }

    //---- Message handlers ---------

    protected function handleMessage(TelegramMessage $message): void
    {
        $this->message = $message;

        $telegramId = $message->from()?->id();
        if (!$telegramId) {
            return;
        }

        $this->setupSubscriber($telegramId, $message->from()?->username());
        $this->subscriber->update(['last_activity_at' => now()]);

        if ($contact = $message->contact()) {
            $this->handleContact($contact);
            return;
        }

        $textInput = $message->text();

        // Проверяем активную сессию сценария
        $activeSession = ConversationSession::where('bot_subscriber_id', $this->subscriber->id)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->first();

        if ($activeSession) {
            if ($textInput !== null) {
                $flowVersion = $activeSession->flowVersion;
                $runner = new FlowRunner($this->bot, $this->subscriber, $flowVersion);
                $runner->handleInput($textInput);
            }
            return;
        }

        // Нет сессии — ищем триггер
        if ($textInput && str_starts_with($textInput, '/')) {
            $command = ltrim($textInput, '/');

            $flow = Flow::where('bot_id', $this->bot->id)
                ->where('trigger_type', TriggerTypes::COMMAND)
                ->where('trigger_value', $command)
                ->where('status', FlowStatus::ACTIVE)
                ->first();

            if ($flow) {
                $version = $flow->versions()
                    ->where('status', FlowVersionStatus::PUBLISHED)
                    ->latest('published_at')
                    ->first();

                if ($version) {
                    $runner = new FlowRunner($this->bot, $this->subscriber, $version);
                    $runner->start();
                    return;
                }
            }
        }

        $text = Str::of($message->text());

        if ($this->isCommand($text)) {
            $this->handleCommand($text);
            return;
        }

        $this->handleChatMessage($text);
    }

    /**
     * Находим или создаём подписчика бота.
     */
    protected function setupSubscriber(int $telegramId, ?string $username): void
    {
        $this->subscriber = BotSubscriber::firstOrCreate(
            ['bot_id' => $this->bot->id, 'telegram_id' => $telegramId],
            [
                'telegram_username' => $username,
                'status' => SubscriberStatus::ACTIVE,
                'settings' => [],
                'language' => $this->bot->settings['language'] ?? config('app.locale'),
            ]
        );
    }

    /**
     * Обычное (некомандное) сообщение
     */
    protected function handleChatMessage(Stringable $text): void
    {
        $conversation = $this->resolveActiveConversation();

        [$type, $content] = $this->extractContent($text);

        MessageModel::create([
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'type' => $type,
            'content' => $content,
            'telegram_message_id' => $this->message?->id(),
        ]);

        if ($type === 'text') {
            $this->reply("Echo: {$content['text']}", $conversation->id);
        }
    }

    /**
     * @return array{0: string, 1: array}
     */
    protected function extractContent(Stringable $text): array
    {
        if ($photo = $this->message?->photos()->last()) {
            return ['photo', ['file_id' => $photo->id()]];
        }

        if ($document = $this->message?->document()) {
            return ['document', ['file_id' => $document->id()]];
        }

        if ($voice = $this->message?->voice()) {
            return ['voice', ['file_id' => $voice->id()]];
        }

        return ['text', ['text' => (string) $text]];
    }

    protected function handleContact(Contact $contact): void
    {
        app(PhoneMergeService::class)->merge($this->subscriber, $contact->phoneNumber(), $this->bot);

        $conversation = Conversation::create([
            'bot_subscriber_id' => $this->subscriber->id,
            'bot_id' => $this->bot->id,
            'status' => ConversationStatus::ACTIVE,
            'context' => [],
        ]);

        $welcomeText = $this->bot->settings['welcome_message']
            ?? 'Добро пожаловать! Вы успешно авторизованы.';

        $this->reply($welcomeText, $conversation->id);
    }

    protected function resolveActiveConversation(): Conversation
    {
        return Conversation::firstOrCreate(
            ['bot_subscriber_id' => $this->subscriber->id, 'status' => ConversationStatus::ACTIVE],
            ['bot_id' => $this->bot->id, 'context' => []]
        );
    }

    //---- Команды ------

    /**
     * @return Collection<int, Stringable>
     */
    protected function commandPrefixes(): Collection
    {
        return collect(['/'])->map(fn (string $prefix) => Str::of($prefix));
    }

    protected function isCommand(Stringable $text): bool
    {
        return $this->commandPrefixes()->contains(
            fn (Stringable $prefix) => $text->startsWith((string) $prefix)
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function parseCommand(Stringable $text): array
    {
        $command = $text->before('@')->before(' ');
        $parameter = $text->after((string) $command)->after('@')->after(' ');

        foreach ($this->commandPrefixes() as $prefix) {
            if ($command->startsWith((string) $prefix)) {
                $command = $command->after((string) $prefix);
                break;
            }
        }

        return [(string) $command, (string) $parameter];
    }

    protected function handleCommand(Stringable $text): void
    {
        [$command, $parameter] = $this->parseCommand($text);

        if (!$this->canHandle($command)) {
            $this->handleUnknownCommand($text);
            return;
        }

        $this->$command($parameter);
    }

    protected function canHandle(string $action): bool
    {
        return in_array($action, self::ALLOWED_COMMANDS, true)
            && method_exists($this, $action)
            && (new ReflectionMethod($this, $action))->isPublic();
    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        LogService::logWarning('Неизвестная Telegram-команда', [
            'bot_id' => $this->bot->id,
            'command' => (string) $text,
        ]);
    }

    /**
     * Обработчик /start — публичный метод, вызывается динамически из handleCommand()
     */
    public function start(string $parameter): void
    {
        if (is_null($this->subscriber->person_id)) {
            $this->requestContact();
            return;
        }

        $this->reply($this->bot->settings['welcome_message'] ?? 'С возвращением!');
    }

    protected function requestContact(): void
    {
        SendContactRequest::dispatch($this->bot, $this->subscriber->telegram_id)
            ->onQueue('telegram');
    }

    //---- CallbackQuery ------

    protected function handleCallbackQuery(CallbackQuery $callbackQuery): void
    {
        LogService::logInfo('Callback query received', ['data' => $callbackQuery->data()->toArray()]);
    }

    //---- Helpers -------------------------------------------------------

    protected function reply(string $text, ?int $conversationId = null): void
    {
        SendTelegramMessage::dispatch(
            $this->bot,
            $this->subscriber->telegram_id,
            $text,
            $conversationId
        )->onQueue('telegram');
    }

    /**
     * Вызывается очередью, если задание провалилось на всех попытках ($tries).
     */
    public function failed(\Throwable $exception): void
    {
        LogService::logError('Не удалось обработать update от Telegram', [
            'bot_id' => $this->bot->id,
            'update_id' => $this->update['update_id'] ?? null,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
