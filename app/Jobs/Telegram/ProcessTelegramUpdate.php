<?php
// app/Jobs/ProcessTelegramUpdate.php

namespace App\Jobs\Telegram;

use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Enums\SubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Conversations\Models\Message;
use App\Domain\Conversations\Services\PhoneMergeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTelegramUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

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
            return;
        }

        if (isset($this->update['message'])) {
            $this->handleMessage($this->update['message']);
        } elseif (isset($this->update['callback_query'])) {
            $this->handleCallbackQuery($this->update['callback_query']);
        }
    }

    /**
     * Вызывается очередью, если задание провалилось на всех попытках ($tries).
     */
    public function failed(\Throwable $exception): void
    {
        $trace = [
            'bot_id' => $this->bot->id,
            'update_id' => $this->update['update_id'] ?? null,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ];

        LogService::logError('Не удалось обработать update от Telegram', $trace);
    }

    private function handleMessage(array $message): void
    {
        $telegramId = $message['from']['id'] ?? null;

        if (!$telegramId) {
            return;
        }

        $subscriber = $this->resolveSubscriber($telegramId, $message['from']['username'] ?? null);
        $subscriber->update(['last_activity_at' => now()]);

        if (isset($message['contact'])) {
            $this->handleContact($subscriber, $message['contact']);
            return;
        }

        $conversation = $this->resolveActiveConversation($subscriber);

        // /start без привязанного person_id → запрашиваем контакт
        if (($message['text'] ?? null) === '/start' && is_null($subscriber->person_id)) {
            $this->requestContact($subscriber, $conversation, $message);
            return;
        }

        [$type, $content] = $this->extractMessageContent($message);

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'type' => $type,
            'content' => $content,
            'telegram_message_id' => $message['message_id'] ?? null,
        ]);

        if ($type === 'text') {
            $this->echoTextMessage($subscriber, $conversation, $content['text']);
        }
    }

    private function resolveSubscriber(int $telegramId, ?string $username): BotSubscriber
    {
        return BotSubscriber::firstOrCreate(
            ['bot_id' => $this->bot->id, 'telegram_id' => $telegramId],
            [
                'telegram_username' => $username,
                'status' => SubscriberStatus::ACTIVE,
                'settings' => [],
                'language' => $this->bot->settings['language'] ?? config('app.locale'),
            ]
        );
    }

    private function resolveActiveConversation(BotSubscriber $subscriber): Conversation
    {
        return Conversation::firstOrCreate(
            ['bot_subscriber_id' => $subscriber->id, 'status' => ConversationStatus::ACTIVE],
            ['bot_id' => $this->bot->id, 'context' => []]
        );
    }

    /**
     * Определяет тип и содержимое входящего сообщения.
     *
     * @return array{0: string, 1: array}
     */
    private function extractMessageContent(array $message): array
    {
        if (isset($message['text'])) {
            return ['text', ['text' => $message['text']]];
        }

        if (isset($message['photo'])) {
            return ['photo', ['file_id' => $message['photo'][array_key_last($message['photo'])]['file_id'] ?? null]];
        }

        if (isset($message['document'])) {
            return ['document', ['file_id' => $message['document']['file_id']]];
        }

        if (isset($message['voice'])) {
            return ['voice', ['file_id' => $message['voice']['file_id']]];
        }

        return ['text', ['text' => '']];
    }

    private function requestContact(BotSubscriber $subscriber, Conversation $conversation, array $message): void
    {
        SendContactRequest::dispatch($this->bot, $subscriber->telegram_id)
            ->onQueue('telegram');

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'type' => 'text',
            'content' => ['text' => '/start'],
            'telegram_message_id' => $message['message_id'] ?? null,
        ]);
    }

    private function echoTextMessage(BotSubscriber $subscriber, Conversation $conversation, string $text): void
    {
        SendTelegramMessage::dispatch(
            $this->bot,
            $subscriber->telegram_id,
            "Echo: {$text}",
            $conversation->id
        )->onQueue('telegram');
    }

    private function handleContact(BotSubscriber $subscriber, array $contact): void
    {
        $phone = $contact['phone_number'] ?? null;
        if (!$phone) {
            return;
        }

        app(PhoneMergeService::class)->merge($subscriber, $phone, $this->bot);

        $welcomeText = $this->bot->settings['welcome_message']
            ?? 'Добро пожаловать! Вы успешно авторизованы.';

        // Создаём новую чистую conversation (сессия сброшена)
        $conversation = Conversation::create([
            'bot_subscriber_id' => $subscriber->id,
            'bot_id' => $this->bot->id,
            'status' => ConversationStatus::ACTIVE,
            'context' => [],
        ]);

        SendTelegramMessage::dispatch(
            $this->bot,
            $subscriber->telegram_id,
            $welcomeText,
            $conversation->id
        )->onQueue('telegram');
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        Log::info('Callback query received', ['data' => $callbackQuery['data'] ?? null]);
    }
}
