<?php
// app/Jobs/ProcessTelegramUpdate.php

namespace App\Jobs\Telegram;

use App\Domain\Bots\Models\Bot;
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

    private function handleMessage(array $message): void
    {
        $from = $message['from'] ?? [];
        $telegramId = $from['id'] ?? null;
        $username = $from['username'] ?? null;

        if (!$telegramId) {
            return;
        }

        $subscriber = BotSubscriber::firstOrCreate(
            ['bot_id' => $this->bot->id, 'telegram_id' => $telegramId],
            [
                'telegram_username' => $username,
                'status' => 'active',
                'settings' => [],
                'language' => $this->bot->settings['language'] ?? config('app.locale'),
            ]
        );

        $subscriber->update(['last_activity_at' => now()]);

        // Обработка контакта
        if (isset($message['contact'])) {
            $this->handleContact($subscriber, $message['contact']);
            return;
        }

        // Conversation
        $conversation = Conversation::firstOrCreate(
            ['bot_subscriber_id' => $subscriber->id, 'status' => 'active'],
            ['bot_id' => $this->bot->id, 'context' => []]
        );

        $type = 'text';
        $content = [];

        if (isset($message['text'])) {
            $type = 'text';
            $content = ['text' => $message['text']];

            // /start без people_id → запрашиваем контакт
            if ($message['text'] === '/start' && is_null($subscriber->people_id)) {
                SendContactRequest::dispatch($this->bot, $subscriber->telegram_id)
                    ->onQueue('telegram');

                Message::create([
                    'conversation_id' => $conversation->id,
                    'direction' => 'in',
                    'type' => 'text',
                    'content' => ['text' => '/start'],
                    'telegram_message_id' => $message['message_id'] ?? null,
                ]);
                return;
            }
        } elseif (isset($message['photo'])) {
            $type = 'photo';
            $content = ['file_id' => $message['photo'][array_key_last($message['photo'])]['file_id'] ?? null];
        } elseif (isset($message['document'])) {
            $type = 'document';
            $content = ['file_id' => $message['document']['file_id']];
        } elseif (isset($message['voice'])) {
            $type = 'voice';
            $content = ['file_id' => $message['voice']['file_id']];
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'type' => $type,
            'content' => $content,
            'telegram_message_id' => $message['message_id'] ?? null,
        ]);

        // Echo
        if ($type === 'text') {
            SendTelegramMessage::dispatch(
                $this->bot,
                $subscriber->telegram_id,
                "Echo: " . $content['text'],
                $conversation->id
            )->onQueue('telegram');
        }
    }

    private function handleContact(BotSubscriber $subscriber, array $contact): void
    {
        $phone = $contact['phone_number'] ?? null;
        if (!$phone) {
            return;
        }

        $mergeService = app(PhoneMergeService::class);
        $mergeService->merge($subscriber, $phone, $this->bot);

        // Приветствие после успешного merge
        $welcomeText = $this->bot->settings['welcome_message']
            ?? 'Добро пожаловать! Вы успешно авторизованы.';

        // Создаём новую чистую conversation (сессия сброшена)
        $conversation = Conversation::create([
            'bot_subscriber_id' => $subscriber->id,
            'bot_id' => $this->bot->id,
            'status' => 'active',
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
