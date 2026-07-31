<?php

namespace App\Models\User\Traits;

use App\Models\User\UserSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

trait HasUserSettings
{
    public function settings(): HasMany
    {
        return $this->hasMany(UserSetting::class);
    }

    /**
     * Получить значение настройки.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = Cache::rememberForever(
            $this->settingCacheKey($key),
            fn () => $this->settings()->where('key', $key)->first(['value', 'encrypted'])
        );

        if ($setting === null || $setting->value === null) {
            return $default;
        }

        $rawValue = $setting->encrypted
            ? $this->decryptValue($setting->value)
            : $setting->value;

        if ($rawValue === null) {
            return $default;
        }

        $decoded = json_decode($rawValue, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $rawValue;
    }

    /**
     * Получить все настройки пользователя в виде ассоциативного массива key => value.
     */
    public function getAllSettings(): array
    {
        return $this->settings()
            ->get(['key', 'value', 'encrypted'])
            ->mapWithKeys(function (UserSetting $setting) {
                if ($setting->value === null) {
                    return [$setting->key => null];
                }

                $rawValue = $setting->encrypted
                    ? $this->decryptValue($setting->value)
                    : $setting->value;

                if ($rawValue === null) {
                    return [$setting->key => null];
                }

                $decoded = json_decode($rawValue, true);
                $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $rawValue;

                return [$setting->key => $value];
            })
            ->toArray();
    }

    /**
     * Установить (создать или обновить) настройку.
     */
    public function setSetting(
        string $key,
        mixed $value,
        ?string $name = null,
        bool $encrypted = false
    ): UserSetting {
        $storedValue = is_scalar($value) || $value === null
            ? (string) $value
            : json_encode($value);

        if ($encrypted) {
            $storedValue = Crypt::encryptString($storedValue);
        }

        $setting = $this->settings()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'name' => $name,
                'encrypted' => $encrypted,
            ]
        );

        Cache::forget($this->settingCacheKey($key));

        return $setting;
    }

    public function hasSetting(string $key): bool
    {
        return $this->settings()->where('key', $key)->exists();
    }

    public function forgetSetting(string $key): bool
    {
        Cache::forget($this->settingCacheKey($key));

        return (bool) $this->settings()->where('key', $key)->delete();
    }

    protected function decryptValue(string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Значение повреждено, либо APP_KEY поменялся — не роняем приложение
            report(new \RuntimeException("Не удалось расшифровать настройку для user_id={$this->id}"));

            return null;
        }
    }

    protected function settingCacheKey(string $key): string
    {
        return "user:{$this->id}:setting:{$key}";
    }
}
