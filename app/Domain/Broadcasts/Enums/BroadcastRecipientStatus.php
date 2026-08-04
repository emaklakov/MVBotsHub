<?php

namespace App\Domain\Broadcasts\Enums;

enum BroadcastRecipientStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function toString(): ?string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::SENT => 'Отправлено',
            self::FAILED => 'Ошибка',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::SENT => 'green',
            self::FAILED => 'red',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'arrow-path-rounded-square',
            self::SENT => 'check',
            self::FAILED => 'x-circle',
        };
    }
}
