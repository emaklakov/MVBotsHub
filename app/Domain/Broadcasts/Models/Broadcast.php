<?php

namespace App\Domain\Broadcasts\Models;

use App\Domain\Audiences\Models\Audience;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Users\Traits\LogsUserActivity;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Bots\Models\Bot;
use App\Domain\Flows\Models\FlowVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model
{
    use HasFactory, LogsUserActivity;

    protected static function logLabel(): string
    {
        return 'Рассылка';
    }

    protected $fillable = [
        'bot_id',
        'audience_id',
        'flow_version_id',
        'name',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => BroadcastStatus::class,
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function audience(): BelongsTo
    {
        return $this->belongsTo(Audience::class);
    }

    public function flowVersion(): BelongsTo
    {
        return $this->belongsTo(FlowVersion::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastRecipient::class);
    }
}
