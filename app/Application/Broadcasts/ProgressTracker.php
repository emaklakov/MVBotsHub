<?php

declare(strict_types=1);

namespace App\Application\Broadcasts;

use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Models\Broadcast;
use Illuminate\Support\Facades\Redis;

/**
 * Атомарный трекер прогресса рассылки.
 * Использует Redis для скорости, DB для консистентности.
 */
final class ProgressTracker
{
    private const string SENT_KEY = 'broadcast:%d:sent';
    private const string FAILED_KEY = 'broadcast:%d:failed';
    private const int FLUSH_BATCH_SIZE = 50;

    public function markSent(int $broadcastId): void
    {
        Redis::incr(sprintf(self::SENT_KEY, $broadcastId));
        $this->maybeFlush($broadcastId);
    }

    public function markFailed(int $broadcastId): void
    {
        Redis::incr(sprintf(self::FAILED_KEY, $broadcastId));
        $this->maybeFlush($broadcastId);
    }

    private function maybeFlush(int $broadcastId): void
    {
        $sent = (int) Redis::get(sprintf(self::SENT_KEY, $broadcastId));
        $failed = (int) Redis::get(sprintf(self::FAILED_KEY, $broadcastId));
        $total = $sent + $failed;

        if ($total === 0 || $total % self::FLUSH_BATCH_SIZE !== 0) {
            return;
        }

        // Атомарный flush счётчиков в БД
        Broadcast::where('id', $broadcastId)->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
        ]);

        $this->maybeComplete($broadcastId, $sent, $failed, $total);
    }

    private function maybeComplete(int $broadcastId, int $sent, int $failed, int $total): void
    {
        // Атомарная проверка: только один Job завершит рассылку
        $affected = Broadcast::where('id', $broadcastId)
            ->where('status', BroadcastStatus::PROCESSING)
            ->whereColumn('total_recipients', '<=', 'sent_count + failed_count') // или whereRaw
            ->update([
                'status' => BroadcastStatus::COMPLETED,
                'completed_at' => now(),
                'sent_count' => $sent,
                'failed_count' => $failed,
            ]);

        if ($affected > 0) {
            Redis::del(sprintf(self::SENT_KEY, $broadcastId));
            Redis::del(sprintf(self::FAILED_KEY, $broadcastId));
        }
    }
}
