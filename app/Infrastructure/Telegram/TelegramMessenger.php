<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use App\Domain\Bots\Models\Bot;
use App\Domain\Flows\Contracts\MessengerInterface;
use App\Jobs\Telegram\SendTelegramMessage;

final class TelegramMessenger implements MessengerInterface
{
    public function sendText(Bot $bot, int $telegramId, string $text, ?array $replyMarkup = null): void
    {
        SendTelegramMessage::dispatch(
            $bot,
            $telegramId,
            $text,
            replyMarkup: $replyMarkup
        )->onQueue('telegram');
    }

    public function sendInlineKeyboard(Bot $bot, int $telegramId, string $text, array $inlineKeyboard): void
    {
        SendTelegramMessage::dispatch(
            $bot,
            $telegramId,
            $text,
            inlineKeyboard: $inlineKeyboard
        )->onQueue('telegram');
    }
}
