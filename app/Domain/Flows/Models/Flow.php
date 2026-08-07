<?php

namespace App\Domain\Flows\Models;

use App\Domain\Bots\Models\Bot;
use App\Domain\Flows\Enums\FlowStatus;
use App\Domain\Users\Traits\LogsUserActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Flow extends Model
{
    use HasFactory, LogsUserActivity;

    protected static function logLabel(): string
    {
        return 'Поток';
    }

    protected $fillable = [
        'bot_id',
        'name',
        'trigger_type',
        'trigger_value',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => FlowStatus::class,
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FlowVersion::class);
    }

    public function latestPublishedVersion(): HasOne
    {
        return $this->hasOne(FlowVersion::class)
            ->where('status', 'published')
            ->latest('published_at');
    }

    public function draftVersion(): HasOne
    {
        return $this->hasOne(FlowVersion::class)
            ->where('status', 'draft');
    }
}
