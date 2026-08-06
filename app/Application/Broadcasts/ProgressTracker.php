<?php

declare(strict_types=1);

namespace App\Application\Broadcasts;

use App\Domain\Broadcasts\Enums\BroadcastRecipientStatus;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Models\Broadcast;
use App\Domain\Broadcasts\Models\BroadcastRecipient;

final class ProgressTracker
{
    public function markSent(int $broadcastId): void
    {
        $this->flush($broadcastId);
    }

    public function markFailed(int $broadcastId): void
    {
        $this->flush($broadcastId);
    }

    private function flush(int $broadcastId): void
    {
        // Подсчитываем актуальные значения из БД
        $sent = BroadcastRecipient::where('broadcast_id', $broadcastId)
            ->where('status', BroadcastRecipientStatus::SENT)
            ->count();

        $failed = BroadcastRecipient::where('broadcast_id', $broadcastId)
            ->where('status', BroadcastRecipientStatus::FAILED)
            ->count();

        Broadcast::where('id', $broadcastId)->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
        ]);

        $this->maybeComplete($broadcastId);
    }

    private function maybeComplete(int $broadcastId): void
    {
        $hasPending = BroadcastRecipient::where('broadcast_id', $broadcastId)
            ->where('status', BroadcastRecipientStatus::PENDING)
            ->exists();

        if ($hasPending) {
            return;
        }

        // Атомарно: только один Job установит COMPLETED
        Broadcast::where('id', $broadcastId)
            ->where('status', BroadcastStatus::PROCESSING)
            ->update([
                'status' => BroadcastStatus::COMPLETED,
                'completed_at' => now(),
            ]);
    }
}
