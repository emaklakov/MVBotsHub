<?php

namespace App\Domain\Conversations\Enums;

enum BotSubscriberStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case MERGED = 'merged';
    case DISABLED = 'disabled';

    public function toString(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активен',
            self::BLOCKED => 'Заблокировал',
            self::MERGED => 'Объединён',
            self::DISABLED => 'Отключен',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::BLOCKED => 'red',
            self::DISABLED => 'grey',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'play-circle',
            self::BLOCKED => 'stop-circle',
            self::MERGED => 'x-circle',
            self::DISABLED => 'stop-circle',
        };
    }
}
