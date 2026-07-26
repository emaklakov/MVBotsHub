<?php

namespace App\Models\Concerns;

use App\Models\UserCode;
use Illuminate\Support\Facades\Hash;

/**
 * Трейт для работы с двухфакторной аутентификацией через электронную почту:
 * генерация кода, проверка кода, очистка кода и взаимодействие с моделями пользователей.
 */
trait HasTwoFactorEmailCode
{
    protected string $twoFactorCodeType = 'two_factor_email';

    public function generateTwoFactorCode(): string
    {
        $existing = $this->userCodes()
            ->where('type_code', $this->twoFactorCodeType)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->latest('id')
            ->first();

        if ($existing) {
            // уже отправляли код только что — не плодим новые
            return '';
        }

        $this->userCodes()
            ->where('type_code', $this->twoFactorCodeType)
            ->delete();

        $code = (string) random_int(100000, 999999);

        $this->userCodes()->create([
            'type_code' => $this->twoFactorCodeType,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        return $code;
    }

    public function checkTwoFactorCode(string $code): bool
    {
        if (app()->environment('local')) {
            return true;
        }

        $userCode = $this->userCodes()
            ->where('type_code', $this->twoFactorCodeType)
            ->latest('id')
            ->first();

        if (! $userCode || $userCode->isExpired()) {
            return false;
        }

        return Hash::check($code, $userCode->code);
    }

    public function clearTwoFactorCode(): void
    {
        $this->userCodes()
            ->where('type_code', $this->twoFactorCodeType)
            ->delete();
    }

    public function userCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserCode::class, 'user_id');
    }
}
