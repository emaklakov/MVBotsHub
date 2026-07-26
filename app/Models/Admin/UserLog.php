<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Модель для логирования действий пользователей:
 * логирует действия пользователей в системе, включая вход, выход, изменения данных и другие события.
 */
class UserLog extends Model
{
    public $timestamps = false;

    protected $table = 'users_logs';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'changes',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
