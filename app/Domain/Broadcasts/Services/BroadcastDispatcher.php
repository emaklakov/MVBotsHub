<?php

namespace App\Domain\Broadcasts\Services;

use App\Domain\Broadcasts\Enums\BroadcastRecipientStatus;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Models\Broadcast;
use App\Domain\Broadcasts\Models\BroadcastRecipient;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Jobs\Telegram\SendBroadcastMessage;
use Illuminate\Support\Facades\Redis;

class BroadcastDispatcher
{
    public function dispatch(Broadcast $broadcast): void
    {
        if ($broadcast->status !== BroadcastStatus::PENDING) {
            return;
        }

        // Собираем всех активных подписчиков бота
        $subscribers = BotSubscriber::where('bot_id', $broadcast->bot_id)
            ->where('status', 'active')
            ->pluck('id');

        $total = $subscribers->count();

        // Создаём записи получателей
        $recipients = $subscribers->map(fn($id) => [
            'broadcast_id' => $broadcast->id,
            'bot_subscriber_id' => $id,
            'status' => BroadcastRecipientStatus::PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ])->chunk(1000);

        foreach ($recipients as $chunk) {
            BroadcastRecipient::insert($chunk->toArray());
        }

        $broadcast->update([
            'status' => BroadcastStatus::PROCESSING,
            'total_recipients' => $total,
            'sent_count' => 0,
            'failed_count' => 0,
            'started_at' => now(),
        ]);

        // Инициализируем Redis-счётчики
        Redis::set("broadcast:{$broadcast->id}:sent", 0);
        Redis::set("broadcast:{$broadcast->id}:failed", 0);

        // Диспатчим джобы
        foreach ($subscribers as $subscriberId) {
            SendBroadcastMessage::dispatch($broadcast->id, $subscriberId)
                ->onQueue('broadcast');
        }
    }

    public function retryFailed(Broadcast $broadcast): void
    {
        $failedIds = BroadcastRecipient::where('broadcast_id', $broadcast->id)
            ->where('status', BroadcastRecipientStatus::FAILED)
            ->pluck('bot_subscriber_id');

        BroadcastRecipient::where('broadcast_id', $broadcast->id)
            ->where('status', BroadcastRecipientStatus::FAILED)
            ->update(['status' => BroadcastRecipientStatus::PENDING, 'attempts' => 0, 'error' => null]);

        $broadcast->update(['status' => BroadcastStatus::PROCESSING]);

        foreach ($failedIds as $subscriberId) {
            SendBroadcastMessage::dispatch($broadcast->id, $subscriberId)
                ->onQueue('broadcast');
        }
    }
}
