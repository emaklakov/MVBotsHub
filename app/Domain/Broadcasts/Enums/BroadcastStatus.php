<?php

namespace App\Domain\Broadcasts\Enums;

enum BroadcastStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function toString(): ?string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::PROCESSING => 'Обработка',
            self::COMPLETED => 'Завершено',
            self::CANCELLED => 'Отменено',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'info',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'arrow-path-rounded-square',
            self::PROCESSING => 'play-circle',
            self::COMPLETED => 'check',
            self::CANCELLED => 'stop-circle',
        };
    }
}
