<?php

namespace App\Application\Telegram\DTO;

use App\Domain\Bots\Models\Bot;

final readonly class SendMessage
{
    public function __construct(
        public Bot $bot,
        public int|string $chatId,
        public string $text,
        public ?int $conversationId = null,
        public ?array $replyMarkup = null,
        public ?array $inlineKeyboard = null,
    ) {
        if (trim($text) === '') {
            throw new \InvalidArgumentException('Текст сообщения не может быть пустым');
        }
    }

    public function hasKeyboard(): bool
    {
        return $this->replyMarkup !== null || $this->inlineKeyboard !== null;
    }

    public function keyboard(): ?array
    {
        return $this->replyMarkup ?? $this->inlineKeyboard;
    }
}
