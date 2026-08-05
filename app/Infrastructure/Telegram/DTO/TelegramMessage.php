<?php

namespace App\Infrastructure\Telegram\DTO;

use DefStudio\Telegraph\DTO\Message;

class TelegramMessage extends Message
{
    public function __construct(
        public int $messageId,
        public TelegramUser $from,
        public string $text,
        public ?TelegramContact $contact,
        public ?array $photo,
    ) {}
}
