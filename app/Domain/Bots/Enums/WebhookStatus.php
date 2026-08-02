<?php

namespace App\Domain\Bots\Enums;

enum WebhookStatus: string
{
    case SET = 'set';
    case NOT_SET = 'not_set';

    public function toString(): ?string
    {
        return match ($this) {
            self::SET => 'Установлен',
            self::NOT_SET => 'Не установлен',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::SET => 'green',
            self::NOT_SET => 'red',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SET => 'check',
            self::NOT_SET => 'x-mark',
        };
    }
}
