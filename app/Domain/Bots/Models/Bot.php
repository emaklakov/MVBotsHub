<?php
// app/Domain/Bots/Models/Bot.php

namespace App\Domain\Bots\Models;

use App\Domain\Bots\Enums\BotChannelType;
use App\Domain\Bots\Enums\BotStatus;
use App\Domain\Bots\Enums\WebhookStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Bot extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'name',
        'description',
        'token',
        'webhook_token',
        'webhook_secret_token',
        'webhook_url',
        'settings',
        'status',
        'channel_type',
    ];

    protected function casts(): array
    {
        return [
            'status' => BotStatus::class,
            'channel_type' => BotChannelType::class,
            'settings' => 'array',
        ];
    }

    protected function botTokenStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->token,
        );
    }

    protected function webhookStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => filled($this->webhook_url) ? WebhookStatus::SET : WebhookStatus::NOT_SET,
        );
    }

    /**
     * Автоматическое шифрование/дешифрование токена
     */
    protected function token(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function getRouteKeyName(): string
    {
        return 'webhook_token';
    }

    public function members(): HasMany
    {
        return $this->hasMany(BotMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bot_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function subscribers()
    {
        return $this->hasMany(BotSubscriber::class);
    }
}
