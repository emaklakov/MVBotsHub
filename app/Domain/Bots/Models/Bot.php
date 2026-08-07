<?php

namespace App\Domain\Bots\Models;

use App\Application\Services\LogService;
use App\Domain\Bots\Enums\BotChannelType;
use App\Domain\Bots\Enums\BotStatus;
use App\Domain\Bots\Enums\WebhookStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Users\Traits\LogsUserActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Bot extends Model
{
    use HasFactory, LogsUserActivity;

    protected static function logLabel(): string
    {
        return 'Бот';
    }

    /**
     * Поля, которые никогда не должны попадать в лог изменений
     */
    protected static array $logExcludedFields = [
        'webhook_token',
        'webhook_secret_token',
        'token',
    ];

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
            get: fn () => !empty($this->token) ? 'set' : 'not_set',
        );
    }

    protected function webhookStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => filled($this->webhook_url) ? WebhookStatus::SET : WebhookStatus::NOT_SET,
        );
    }

    /**
     * Автоматическое шифрование/дешифрование токена.
     * Расшифровка защищена от исключений (например, при ротации APP_KEY
     * или повреждении данных), чтобы не ронять страницу списка/формы бота.
     */
    protected function token(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (\Throwable $exception) {
                    LogService::logError('Не удалось расшифровать токен бота bot_id:'.$this->id.' - '.$exception->getMessage(), $exception->getTraceAsString());
                    return null;
                }
            },
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Безопасное превью токена для UI: последние 4 символа + статус.
     * Не выбрасывает исключение, если токен не расшифровывается.
     */
    public function maskedTokenPreview(): string
    {
        $token = $this->token;

        return $token
            ? 'Установлен (••••' . substr($token, -4) . ')'
            : 'Не задан';
    }

    public function getRouteKeyName(): string
    {
        return 'webhook_token';
    }

    public function members(): HasMany
    {
        return $this->hasMany(BotMember::class);
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(BotSubscriber::class);
    }

    public function messageTemplates(): HasMany
    {
        return $this->hasMany(BotMessageTemplate::class);
    }
}
