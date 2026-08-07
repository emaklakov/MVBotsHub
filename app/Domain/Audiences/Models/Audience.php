<?php

namespace App\Domain\Audiences\Models;

use App\Domain\Audiences\Enums\AudienceType;
use App\Domain\Bots\Models\Bot;
use App\Domain\Broadcasts\Models\Broadcast;
use App\Domain\Conversations\Models\BotSubscriber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audience extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id',
        'name',
        'type',
        'filters',
        'cached_count',
        'cached_count_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AudienceType::class,
            'filters' => 'array',
            'cached_count_at' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(Broadcast::class);
    }

    /**
     * Только для type=static.
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(BotSubscriber::class, 'audience_subscriber');
    }
}
