<?php

declare(strict_types=1);

namespace App\Application\Broadcasts\Services;

use App\Domain\Broadcasts\Enums\BroadcastRecipientStatus;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Models\Broadcast;
use App\Domain\Broadcasts\Models\BroadcastRecipient;
use App\Domain\Conversations\Enums\SubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;

final class BroadcastRecipientGenerator
{
    /**
     * Создаёт получателей для рассылки из всех активных подписчиков бота.
     * Пропускает дубли (если уже созданы).
     */
    public function generate(Broadcast $broadcast): int
    {
        $existingSubscriberIds = BroadcastRecipient::query()
            ->where('broadcast_id', $broadcast->id)
            ->pluck('bot_subscriber_id')
            ->toArray();

        $subscribers = BotSubscriber::query()
            ->where('bot_id', $broadcast->bot_id)
            ->where('status', SubscriberStatus::ACTIVE)
            ->whereNotIn('id', $existingSubscriberIds)
            ->pluck('id');

        $now = now();
        $records = $subscribers->map(fn(int $id) => [
            'broadcast_id' => $broadcast->id,
            'bot_subscriber_id' => $id,
            'status' => BroadcastRecipientStatus::PENDING,
            'attempts' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        if (!empty($records)) {
            BroadcastRecipient::insert($records);
        }

        // Обновляем счётчик total_recipients
        $total = BroadcastRecipient::where('broadcast_id', $broadcast->id)->count();
        $broadcast->update(['total_recipients' => $total]);

        return count($records);
    }

    /**
     * Добавить одного подписчика во все pending-рассылки бота.
     * Вызывать при подписке нового пользователя.
     */
    public function addSubscriberToPendingBroadcasts(BotSubscriber $subscriber): void
    {
        $pendingBroadcasts = Broadcast::query()
            ->where('bot_id', $subscriber->bot_id)
            ->where('status', BroadcastStatus::PENDING)
            ->pluck('id');

        $now = now();
        $records = $pendingBroadcasts->map(fn(int $id) => [
            'broadcast_id' => $id,
            'bot_subscriber_id' => $subscriber->id,
            'status' => BroadcastRecipientStatus::PENDING,
            'attempts' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        if (!empty($records)) {
            BroadcastRecipient::insert($records);
        }
    }
}
