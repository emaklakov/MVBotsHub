<?php

namespace App\Domain\Broadcasts\Models;

use App\Domain\Broadcasts\Enums\BroadcastRecipientStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'broadcast_id',
        'bot_subscriber_id',
        'status',
        'sent_at',
        'error',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'status' => BroadcastRecipientStatus::class,
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(BotSubscriber::class, 'bot_subscriber_id');
    }
}
