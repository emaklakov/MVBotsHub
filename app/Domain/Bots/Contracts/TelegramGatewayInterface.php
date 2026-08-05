<?php

declare(strict_types=1);

namespace App\Domain\Bots\Contracts;

use App\Domain\Bots\Models\Bot;

interface TelegramGatewayInterface
{
    public function answerCallbackQuery(Bot $bot, int $callbackQueryId): void;
}
