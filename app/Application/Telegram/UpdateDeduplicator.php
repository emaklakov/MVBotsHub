<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use Illuminate\Support\Facades\DB;

/**
 * Атомарная дедупликация через INSERT с игнорированием дубликатов.
 * Обернута в транзакцию для защиты от race condition.
 */
final class UpdateDeduplicator
{
    public function acquire(int $botId, int $updateId): bool
    {
        return DB::transaction(static function () use ($botId, $updateId) {
            return DB::table('processed_updates')->insertOrIgnore([
                    'bot_id'      => $botId,
                    'update_id'   => $updateId,
                    'processed_at' => now(),
                ]) > 0;
        });
    }
}
