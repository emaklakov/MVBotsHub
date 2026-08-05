<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use App\Domain\Bots\Contracts\TelegramGatewayInterface;
use App\Domain\Bots\Models\Bot;
use DefStudio\Telegraph\Facades\Telegraph;

final class TelegraphGateway implements TelegramGatewayInterface
{
    public function answerCallbackQuery(Bot $bot, int $callbackQueryId): void
    {
        Telegraph::bot($bot->token)->replyWebhook(callbackQueryId: $callbackQueryId);
    }
}
