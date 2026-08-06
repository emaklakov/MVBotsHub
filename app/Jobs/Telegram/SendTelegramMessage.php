<?php

namespace App\Jobs\Telegram;

use App\Application\Services\LogService;
use App\Application\Telegram\DTO\SendMessage;
use App\Application\Telegram\MessageRecorder;
use App\Domain\Bots\Contracts\TelegramGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class SendTelegramMessage implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120];

    public function __construct(
        private readonly SendMessage $sendMessage,
    ) {}

    /**
     * Уникальный ключ: защита от дублирования одного и того же сообщения.
     */
    public function uniqueId(): string
    {
        return sprintf(
            'telegram-msg:%d:%s:%s',
            $this->sendMessage->bot->id,
            $this->sendMessage->chatId,
            md5($this->sendMessage->text)
        );
    }

    public function middleware(): array
    {
        return [new RateLimited('telegram')];
    }

    public function handle(
        TelegramGatewayInterface $telegramGateway,
        MessageRecorder $messageRecorder
    ): void
    {
        try {
            $telegramMessageId = $telegramGateway->send($this->sendMessage);

            if ($this->sendMessage->conversationId) {
                $messageRecorder->recordOutbound(
                    $this->sendMessage->conversationId,
                    'text',
                    ['text' => $this->sendMessage->text],
                    $telegramMessageId
                );
            }

        } catch (RequestException $exception) {
            $response = $exception->response;

            // Обработка 429 Too Many Requests
            if ($response->status() === 429) {
                $retryAfter = $response->json('parameters.retry_after', 60);
                LogService::logWarning('Telegram 429', [
                    'bot_id' => $this->sendMessage->bot->id,
                    'retry_after' => $retryAfter,
                ]);
                $this->release($retryAfter);
                return;
            }

            LogService::logError('Telegram API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw $exception;

        } catch (\Throwable $exception) {
            LogService::logError('Send message failed', [
                'bot_id' => $this->sendMessage->bot->id,
                'chat_id' => $this->sendMessage->chatId,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        LogService::logError('Send message failed', [
            'bot_id'  => $this->sendMessage->bot->id,
            'chat_id' => $this->sendMessage->chatId,
            'error'   => $exception->getMessage(),
        ]);
    }
}
