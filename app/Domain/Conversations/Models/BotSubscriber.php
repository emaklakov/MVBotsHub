<?php
// app/Domain/Conversations/Models/BotSubscriber.php

namespace App\Domain\Conversations\Models;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\SubscriberStatus;
use App\Domain\CRM\Models\Person;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id',
        'person_id',
        'telegram_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_last_name',
        'telegram_language',
        'language',
        'settings',
        'status',
        'merged_into_id',
        'last_activity_at',
        'birthday'
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'status' => SubscriberStatus::class,
            'last_activity_at' => 'datetime',
        ];
    }

    public function effectiveSettings(): Attribute
    {
        return Attribute::make(
            get: function () {
                $botSettings = $this->bot->settings ?? [];
                $subscriberSettings = $this->settings ?? [];
                return array_merge($botSettings, $subscriberSettings);
            }
        );
    }

    public function effectiveLanguage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->language
                ?? $this->bot->settings['language']
                ?? config('app.locale')
        );
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'bot_subscriber_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }
}
