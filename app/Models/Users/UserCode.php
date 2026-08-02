<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель для работы с кодами двухфакторной аутентификации пользователей:
 * генерация кодов, проверка срока действия кодов и взаимодействие с моделями пользователей.
 */
class UserCode extends Model
{
    protected $fillable = [
        'user_id',
        'type_code',
        'code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
