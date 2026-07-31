<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = ['user_id', 'name', 'key', 'value', 'encrypted'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Автоматически кастуем значение при получении/записи (см. ниже про JSON)
    protected function casts(): array
    {
        return [
            'value' => 'string',
            'encrypted' => 'boolean',
        ];
    }
}
