<?php

declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Application\Services\LogService;
use App\Application\Telegram\UpdateDeduplicator;
use App\Application\Telegram\UpdateDispatcher;
use App\Domain\Bots\Models\Bot;
use App\Infrastructure\Telegram\DTO\TelegramUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Тонкий Job-координатор.
 * Отвечает только за: дедупликацию → делегирование → логирование ошибок.
 */
final class ProcessTelegramUpdate implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 60];

    public function __construct(
        private readonly Bot $bot,
        private readonly array $update,
    ) {}

    /**
     * Уникальный ключ для ShouldBeUnique — предотвращает race condition
     * при одновременной обработке update одного пользователя.
     */
    public function uniqueId(): string
    {
        return sprintf('telegram-update-%d-%d', $this->bot->id, $this->update['update_id']);
    }

    public function handle(UpdateDeduplicator $deduplicator, UpdateDispatcher $dispatcher): void
    {
        $updateId = $this->update['update_id'];

        if (!$deduplicator->acquire($this->bot->id, $updateId)) {
            return; // Уже обработан
        }

        try {
            $update = TelegramUpdate::fromArray($this->update);

            $dispatcher->dispatch($this->bot, $update);
        } catch (Throwable $e) {
            LogService::logError('Ошибка обработки Telegram update', [
                'bot_id'    => $this->bot->id,
                'update_id' => $updateId,
                'message'   => $e->getMessage(),
            ]);

            throw $e; // Laravel Queue сам обработает retry/failed
        }
    }

    public function failed(Throwable $exception): void
    {
        LogService::logError('Не удалось обработать update от Telegram', [
            'bot_id'    => $this->bot->id,
            'update_id' => $this->update['update_id'] ?? null,
            'message'   => $exception->getMessage(),
            'trace'     => $exception->getTraceAsString(),
        ]);
    }
}
