<?php

namespace App\Domain\Flows\Enums;

enum TriggerTypes: string
{
    case COMMAND = 'command';
    case CALLBACK = 'callback';
    case DEEPLINK = 'deeplink';
    case BUTTON = 'button';

    public function toString(): ?string
    {
        return match ($this) {
            self::COMMAND => 'Команда (/start, /help и т.д.)',
            self::CALLBACK => 'Callback Query',
            self::DEEPLINK => 'Deep Link',
            self::BUTTON => 'Кнопка',
        };
    }
}
