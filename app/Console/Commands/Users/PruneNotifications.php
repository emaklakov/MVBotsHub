<?php
// app/Console/Commands/PruneMoonShineNotifications.php

declare(strict_types=1);

namespace App\Console\Commands\Users;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

final class PruneNotifications extends Command
{
    protected $signature = 'moonshine:notifications:prune
                            {--days=30 : Удалить уведомления старше N дней}
                            {--read-only : Удалять только прочитанные}
                            {--expired-only : Удалять только просроченные}
                            {--dry-run : Показать сколько будет удалено, не удаляя}';

    protected $description = 'Очистка старых уведомлений MoonShine';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $query = DB::table('notifications')
            ->where('type', DatabaseNotification::class);

        if ($this->option('expired-only')) {
            $query->whereNotNull('expires_at')
                ->where('expires_at', '<', now());
        } else {
            $query->where('created_at', '<', $cutoff);
        }

        if ($this->option('read-only')) {
            $query->whereNotNull('read_at');
        }

        if ($this->option('dry-run')) {
            $this->info("Будет удалено: {$query->count()}");
            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Удалено: {$deleted}");

        return self::SUCCESS;
    }
}
