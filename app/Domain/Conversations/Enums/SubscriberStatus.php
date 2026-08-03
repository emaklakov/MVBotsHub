<?php

namespace App\Domain\Conversations\Enums;

enum SubscriberStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case MERGED = 'merged';

    public function toString(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активен',
            self::BLOCKED => 'Заблокирован',
            self::MERGED => 'Объединён',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::BLOCKED => 'red',
            self::MERGED => 'yellow',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'play-circle',
            self::BLOCKED => 'stop-circle',
            self::MERGED => 'x-circle',
        };
    }
}
