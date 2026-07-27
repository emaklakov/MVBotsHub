<?php

namespace App\Models\Admin\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель для работы с пользовательскими сессиями:
 * предоставляет доступ к данным сессии и связывает с пользователем.
 */
class Session extends Model
{
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'last_activity' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
