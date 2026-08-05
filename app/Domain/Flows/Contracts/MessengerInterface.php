<?php

declare(strict_types=1);

namespace App\Domain\Flows\Contracts;

use App\Domain\Bots\Models\Bot;

interface MessengerInterface
{
    public function sendText(Bot $bot, int $telegramId, string $text, ?array $replyMarkup = null): void;

    public function sendInlineKeyboard(Bot $bot, int $telegramId, string $text, array $inlineKeyboard): void;
}
