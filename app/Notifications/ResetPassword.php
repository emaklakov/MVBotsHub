<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;

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
