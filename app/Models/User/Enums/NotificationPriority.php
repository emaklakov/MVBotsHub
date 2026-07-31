<?php

namespace App\Models\User\Enums;

enum NotificationPriority: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case NORMAL = 'normal';
    case LOW = 'low';

    public function label(): string
    {
        return match ($this) {
            self::CRITICAL => 'Критический',
            self::HIGH => 'Высокий',
            self::NORMAL => 'Обычный',
            self::LOW => 'Низкий',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CRITICAL => 'red',
            self::HIGH => 'orange',
            self::NORMAL => 'purple',
            self::LOW => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CRITICAL => 'fire',
            self::HIGH => 'exclamation-triangle',
            self::NORMAL => 'information-circle',
            self::LOW => 'bell',
        };
    }
}
