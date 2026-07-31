<?php

namespace App\Listeners\Auth;

use App\Application\Services\User\ActivityLogger;
use App\Infrastructure\Notifications\Auth\Email\TwoFactorCodeNotification;
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

        // Сессию выставляем всегда, независимо от того, был ли сгенерирован
        // новый код или сработал антидубль-дебаунс (код уже отправлен недавно).
        session([
            'needs_2fa' => true,
            'needs_2fa_user_id' => $user->getAuthIdentifier(),
        ]);

        // Пустая строка означает "код уже отправляли в последние 5 секунд" —
        // письмо повторно слать не нужно.
        if ($code === '') {
            return;
        }

        try {
            $user->notify(new TwoFactorCodeNotification($code));
        } catch (\Throwable $e) {
            report($e);

            // Отправка не удалась — удаляем только что созданный код,
            // чтобы пользователь мог сразу инициировать повторную отправку
            // (иначе 5-секундный дебаунс заблокирует повтор без всякого смысла).
            $user->userCodes()
                ->where('type_code', $user->twoFactorCodeType)
                ->latest('id')
                ->limit(1)
                ->delete();
        }
    }
}
