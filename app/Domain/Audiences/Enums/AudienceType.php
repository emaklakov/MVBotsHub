<?php

namespace App\Domain\Audiences\Enums;

enum AudienceType: string
{
    case STATIC = 'static';
    case DYNAMIC = 'dynamic';

    public function toString(): string
    {
        return match ($this) {
            self::STATIC => 'Ручной список',
            self::DYNAMIC => 'Динамический сегмент',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::STATIC => 'blue',
            self::DYNAMIC => 'purple',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::STATIC => 'list-bullet',
            self::DYNAMIC => 'funnel',
        };
    }
}
