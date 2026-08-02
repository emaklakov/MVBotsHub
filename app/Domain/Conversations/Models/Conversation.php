<?php

namespace App\Domain\Conversations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_subscriber_id',
        'bot_id',
        'status',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(BotSubscriber::class, 'bot_subscriber_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
