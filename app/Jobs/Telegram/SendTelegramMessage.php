<?php

namespace App\Jobs\Telegram;

use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Models\Message;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120];

    public function __construct(
        public Bot $bot,
        public int|string $chatId,
        public string $text,
        public ?int $conversationId = null,
        public ?array $replyMarkup = null,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('telegram')];
    }

    public function handle(): void
    {
        try {
            $telegraph = Telegraph::bot($this->bot->token)
                ->chat((string) $this->chatId)
                ->html($this->text);

            if ($this->replyMarkup) {
                // если $this->replyMarkup — это полный reply_markup вида
                // ['inline_keyboard' => [...]], достаём саму разметку кнопок;
                // если это уже голый массив рядов кнопок — используем как есть
                $telegraph = $telegraph->keyboard($this->replyMarkup['inline_keyboard'] ?? $this->replyMarkup);
            }

            $response = $telegraph->send();

            $telegramMessageId = null;
            if ($response->successful() && $response->json('ok') === true) {
                $telegramMessageId = $response->json('result.message_id');
            }

            if ($this->conversationId) {
                Message::create([
                    'conversation_id' => $this->conversationId,
                    'direction' => 'out',
                    'type' => 'text',
                    'content' => ['text' => $this->text],
                    'telegram_message_id' => $telegramMessageId,
                    'sent_at' => now(),
                ]);
            }

        } catch (RequestException $e) {
            $response = $e->response;

            // Обработка 429 Too Many Requests
            if ($response->status() === 429) {
                $retryAfter = $response->json('parameters.retry_after', 60);
                LogService::logWarning('Telegram 429', [
                    'bot_id' => $this->bot->id,
                    'retry_after' => $retryAfter,
                ]);
                $this->release($retryAfter);
                return;
            }

            LogService::logError('Telegram API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw $e;

        } catch (\Throwable $e) {
            LogService::logError('Send message failed', [
                'bot_id' => $this->bot->id,
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
