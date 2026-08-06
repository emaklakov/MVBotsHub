<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Models\Bot;
use App\Domain\Flows\Contracts\MessageSenderInterface;
use App\Jobs\Telegram\SendContactRequest;
use App\Jobs\Telegram\SendTelegramMessage;

/**
 * Фасад для отправки сообщений через очередь.
 * Изолирует остальной код от деталей диспатчинга Job'ов.
 */
final class TelegramMessageSender implements MessageSenderInterface
{
    public function send(SendMessage $sendMessage): void
    {
        SendTelegramMessage::dispatch($sendMessage)
            ->onQueue('telegram');
    }

    public function requestContact(Bot $bot, int $telegramId): void
    {
        SendContactRequest::dispatch($bot, $telegramId)
            ->onQueue('telegram');
    }
}
