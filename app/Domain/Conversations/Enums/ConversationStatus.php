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

    public function getColor(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::CLOSED => 'red',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'play-circle',
            self::CLOSED => 'x-circle',
        };
    }
}
