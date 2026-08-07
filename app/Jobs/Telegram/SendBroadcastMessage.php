<?php

declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Application\Broadcasts\Services\BroadcastDispatcher;
use App\Domain\Broadcasts\Exceptions\RateLimitException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

/**
 * Тонкий Job-координатор.
 * Отвечает только за: retry, rate-limit release, uniqueness.
 */
final class SendBroadcastMessage implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 60];

    public function __construct(
        private readonly int $broadcastId,
        private readonly int $subscriberId,
        private readonly int $botId,
    ) {}

    /**
     * Уникальный ключ: один subscriber не получит дубль broadcast.
     */
    public function uniqueId(): string
    {
        return "broadcast:{$this->broadcastId}:subscriber:{$this->subscriberId}";
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('telegram-broadcast:' . $this->botId))
        ];
    }

    public function handle(BroadcastDispatcher $dispatcher): void
    {
        try {
            $dispatcher->dispatch($this->broadcastId, $this->subscriberId);
        } catch (RateLimitException $exception) {
            $this->release($exception->retryAfter);
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Laravel автоматически логирует failed jobs,
        // но можно добавить кастомную метрику/alert здесь
    }
}
