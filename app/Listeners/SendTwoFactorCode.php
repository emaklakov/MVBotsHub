<?php

namespace App\Listeners;

use App\Notifications\TwoFactorCodeNotification;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;

class SendTwoFactorCode
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'moonshine') {
            return;
        }

        $user = $event->user;

        ActivityLogger::log('login', $user, 'Вход в аккаунт', userId: $user->getAuthIdentifier());

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
