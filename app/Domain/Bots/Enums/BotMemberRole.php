<?php

namespace App\Domain\Bots\Enums;

enum BotMemberRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case VIEWER = 'viewer';

    public function toString(): ?string
    {
        return match ($this) {
            self::OWNER => 'Владелец',
            self::ADMIN => 'Администратор',
            self::VIEWER => 'Наблюдатель',
        };
    }
}
