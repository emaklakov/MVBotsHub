<?php

namespace App\Domain\Bots\Enums;

enum BotStatus: string
{
    case DISABLED = 'disabled';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case ARCHIVED = 'archived';

    public function toString(): ?string
    {
        return match ($this) {
            self::DISABLED => 'Отключен',
            self::ACTIVE => 'Активен',
            self::PAUSED => 'Приостановлен',
            self::ARCHIVED => 'Архивирован',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::DISABLED => 'gray',
            self::ACTIVE => 'green',
            self::PAUSED => 'yellow',
            self::ARCHIVED => 'red',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DISABLED => 'stop-circle',
            self::ACTIVE => 'play-circle',
            self::PAUSED => 'pause-circle',
            self::ARCHIVED => 'x-circle',
        };
    }
}
