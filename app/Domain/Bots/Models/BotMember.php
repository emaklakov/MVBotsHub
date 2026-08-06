<?php

namespace App\Domain\Bots\Models;

use App\Domain\Bots\Enums\BotMemberRole;
use App\Domain\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotMember extends Model
{
    protected $fillable = [
        'bot_id',
        'user_id',
        'created_by',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => BotMemberRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
