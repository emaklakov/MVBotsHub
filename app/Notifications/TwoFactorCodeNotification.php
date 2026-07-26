<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Уведомление для отправки кода двухфакторной аутентификации пользователю:
 * отправляет код на электронную почту пользователя для входа в админ-панель.
 */
class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Код подтверждения входа')
            ->greeting('Здравствуйте!')
            ->line('Ваш код для входа в админ-панель:')
            ->line("**{$this->code}**")
            ->line('Код действителен в течение 10 минут.')
            ->line('Если это были не вы — просто проигнорируйте это письмо.');
    }
}
