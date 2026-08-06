<?php

namespace App\Domain\Broadcasts\Enums;

enum BroadcastStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function toString(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::PENDING => 'Ожидает',
            self::PROCESSING => 'Обработка',
            self::COMPLETED => 'Завершена',
            self::CANCELLED => 'Отменена',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'yellow',
            self::PROCESSING => 'info',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => 'pencil-square',
            self::PENDING => 'arrow-path-rounded-square',
            self::PROCESSING => 'play-circle',
            self::COMPLETED => 'check',
            self::CANCELLED => 'stop-circle',
        };
    }
}
