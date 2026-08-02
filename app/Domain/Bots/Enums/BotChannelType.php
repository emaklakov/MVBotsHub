<?php

namespace App\Domain\Bots\Enums;

enum BotChannelType: string
{
    case TELEGRAM = 'telegram';

    public function toString(): ?string
    {
        return match ($this) {
            self::TELEGRAM => 'Telegram',
        };
    }
}
