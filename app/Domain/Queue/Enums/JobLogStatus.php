<?php

namespace App\Domain\Queue\Enums;

enum JobLogStatus: string
{
    case FAILED = 'failed';
    case COMPLETED = 'completed';
    case PROCECCING = 'processing';

    public function toString(): ?string
    {
        return match ($this) {
            self::FAILED => 'failed',
            self::COMPLETED => 'completed',
            self::PROCECCING => 'processing',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::FAILED => 'red',
            self::COMPLETED => 'green',
            self::PROCECCING => 'yellow',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::FAILED => 'stop-circle',
            self::COMPLETED => 'check',
            self::PROCECCING => 'play-circle',
        };
    }
}
