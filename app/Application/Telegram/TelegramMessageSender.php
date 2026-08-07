<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Models\Bot;
use App\Domain\Flows\Contracts\MessageSenderInterface;
use App\Jobs\Telegram\SendContactRequest;
use App\Jobs\Telegram\SendTelegramMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;

/**
 * Фасад для отправки сообщений через очередь.
 * Изолирует остальной код от деталей диспатчинга Job'ов.
 *
 * ВАЖНО: биндится как scoped (см. AppServiceProvider), а не singleton.
 * Инстанс копит $pending за один прогон FlowEngine и должен обнуляться
 * между разными джобами очереди — singleton-биндинг привёл бы к тому,
 * что сообщения одной джобы могли бы утечь в flush() другой.
 */
final class TelegramMessageSender implements MessageSenderInterface
{
    /** @var array<int, ShouldQueue> */
    private array $pending = [];

    public function send(SendMessage $sendMessage): void
    {
        $this->pending[] = new SendTelegramMessage($sendMessage);
    }

    public function requestContact(Bot $bot, int $telegramId): void
    {
        $this->pending[] = new SendContactRequest($bot, $telegramId);
    }

    public function flush(): void
    {
        if (empty($this->pending)) {
            return;
        }

        $jobs = $this->pending;
        $this->pending = [];

        // Bus::chain гарантирует: следующая джоба стартует только после
        // успешного завершения предыдущей (включая её ретраи/release),
        // поэтому сообщения одного прогона flow приходят строго по порядку.
        Bus::chain($jobs)->onQueue('telegram')->dispatch();
    }
}
