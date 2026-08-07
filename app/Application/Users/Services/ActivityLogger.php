<?php

namespace App\Application\Users\Services;

use App\Domain\Users\UserLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для логирования действий пользователей:
 * логирует действия пользователей в системе, включая вход, выход, изменения данных и другие события.
 */
class ActivityLogger
{
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $changes = null,
        ?int $userId = null,
    ): void {
        $userId ??= Auth::guard('moonshine')->id();

        DB::table('users_logs')->insert([
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'changes' => json_encode($changes),
            'created_at' => now(),
        ]);
    }
}
