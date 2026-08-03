<?php

namespace App\Domain\Conversations\Models;

use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Flows\Models\FlowVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_subscriber_id',
        'flow_version_id',
        'current_block_id',
        'context',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'status' => ConversationSessionStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(BotSubscriber::class, 'bot_subscriber_id');
    }

    public function flowVersion(): BelongsTo
    {
        return $this->belongsTo(FlowVersion::class, 'flow_version_id');
    }
}
