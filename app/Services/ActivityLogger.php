<?php

namespace App\Services;

use App\Models\Admin\UserLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

        UserLog::create([
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
