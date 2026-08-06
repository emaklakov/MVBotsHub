<?php

declare(strict_types=1);

namespace App\Domain\Bots\Contracts;

use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Models\Bot;

interface TelegramGatewayInterface
{
    /**
     * Отправить сообщение и вернуть ID сообщения в Telegram.
     *
     * @return int|null Telegram message_id или null при ошибке
     */
    public function send(SendMessage $sendMessage): ?int;

    public function answerCallbackQuery(Bot $bot, int $callbackQueryId): void;
}
