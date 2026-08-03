<?php

namespace App\Domain\Conversations\Enums;

enum ConversationStatus: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';

    public function toString(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активен',
            self::CLOSED => 'Закрыт',
        };
    }
}
