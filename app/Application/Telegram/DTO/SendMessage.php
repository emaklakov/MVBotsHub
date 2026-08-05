<?php

namespace App\Application\Telegram\DTO;

final readonly class SendMessage
{
    public function __construct(
        public int $botId,
        public int $telegramId,
        public string $text,
        public ?array $replyMarkup,
        public ?int $conversationId,
    ) {}
}
