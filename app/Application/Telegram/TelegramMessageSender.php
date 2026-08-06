<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Models\Bot;
use App\Jobs\Telegram\SendContactRequest;
use App\Jobs\Telegram\SendTelegramMessage;

/**
 * Фасад для отправки сообщений через очередь.
 * Изолирует остальной код от деталей диспатчинга Job'ов.
 */
final class TelegramMessageSender
{
    public function send(Bot $bot, int $telegramId, string $text, ?int $conversationId = null): void
    {
        SendTelegramMessage::dispatch(new SendMessage($bot, $telegramId, $text, $conversationId))
            ->onQueue('telegram');
    }

    public function requestContact(Bot $bot, int $telegramId): void
    {
        SendContactRequest::dispatch($bot, $telegramId)
            ->onQueue('telegram');
    }
}
