<?php

namespace App\Listeners;

use App\Notifications\TwoFactorCodeNotification;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;

/**
 * Слушатель для отправки кода двухфакторной аутентификации пользователю:
 * отправляет код на электронную почту пользователя и устанавливает флаг двухфакторной аутентификации в сессии.
 */
class SendTwoFactorCode
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'moonshine') {
            return;
        }

        $user = $event->user;

        ActivityLogger::log('login', $user, 'Вход в аккаунт', userId: $user->getAuthIdentifier());

        if (! $user->enabled_2fa) {
            return;
        }

        $code = $user->generateTwoFactorCode();

        if ($code === '') {
            session([
                'needs_2fa' => true,
                'needs_2fa_user_id' => $user->getAuthIdentifier(),
            ]);
            return;
        }

        $user->notify(new TwoFactorCodeNotification($code));

        session([
            'needs_2fa' => true,
            'needs_2fa_user_id' => $user->getAuthIdentifier(),
        ]);
    }
}
