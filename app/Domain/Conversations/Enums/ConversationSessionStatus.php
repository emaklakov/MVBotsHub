<?php

namespace App\Domain\Conversations\Enums;

enum ConversationSessionStatus: string
{
    case ACTIVE = 'active';
    case PAUSE = 'paused';
    case COMPLETED = 'completed';

    public function toString(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активная',
            self::PAUSE => 'Остановлена',
            self::COMPLETED => 'Завершена',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::PAUSE => 'yellow',
            self::COMPLETED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'play-circle',
            self::PAUSE => 'pause-circle',
            self::COMPLETED => 'stop-circle',
        };
    }
}
