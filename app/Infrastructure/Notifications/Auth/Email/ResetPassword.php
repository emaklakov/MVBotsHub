<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Auth\Email;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;

/**
 * Уведомление для сброса пароля пользователя:
 * отправляет ссылку для сброса пароля на электронную почту пользователя.
 */
final class ResetPassword extends BaseResetPassword
{
    use Queueable;

    protected function resetUrl($notifiable): string
    {
        return url(route('moonshine.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
