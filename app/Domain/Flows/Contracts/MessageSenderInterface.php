<?php

declare(strict_types=1);

namespace App\Domain\Flows\Contracts;

use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Models\Bot;

interface MessageSenderInterface
{
    public function send(SendMessage $sendMessage): void;

    public function requestContact(Bot $bot, int $telegramId): void;
}
