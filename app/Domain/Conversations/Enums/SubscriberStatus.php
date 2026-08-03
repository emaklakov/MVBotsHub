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
}
