<?php

namespace App\Jobs\Telegram;

use App\Application\Services\LogService;
use App\Application\Telegram\DTO\SendMessage;
use App\Application\Telegram\MessageRecorder;
use App\Domain\Bots\Contracts\HasBotRateLimitKey;
use App\Domain\Bots\Contracts\TelegramGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendTelegramMessage implements ShouldQueue, HasBotRateLimitKey
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120];

    public function __construct(
        private readonly SendMessage $sendMessage,
    ) {}

    public function botId(): int
    {
        return $this->sendMessage->bot->id;
    }

    /**
     * Не более одной джобы на chat_id выполняется одновременно — это то,
     * что реально гарантирует порядок доставки при параллельных воркерах
     * очереди 'telegram' (см. config/horizon.php, maxProcesses => 20).
     * Внутри одного прогона FlowEngine порядок уже обеспечен Bus::chain
     * в TelegramMessageSender::flush(), а этот лок защищает от гонки
     * между РАЗНЫМИ источниками отправки в один и тот же чат — например,
     * отложенным ProcessFlowStep и новым входящим сообщением пользователя.
     *
     * RateLimited('telegram') троттлится по botId() (см. AppServiceProvider) —
     * Telegram лимитирует запросы по токену бота, а не по IP сервера, поэтому
     * рассылка одного бота не должна съедать лимит другого бота на том же сервере.
     *
     * ShouldBeUnique с ключом по хэшу текста сюда намеренно не возвращён:
     * он молча дропал легитимные повторные отправки одного и того же
     * текста (например, повторный промпт при невалидном вводе).
     */
    public function middleware(): array
    {
        $chatKey = "telegram-chat:{$this->sendMessage->bot->id}:{$this->sendMessage->chatId}";

        return [
            (new WithoutOverlapping($chatKey))->releaseAfter(2)->expireAfter(180),
            new RateLimited('telegram'),
        ];
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
