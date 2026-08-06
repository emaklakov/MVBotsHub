<?php

namespace App\Application\Telegram;

use App\Domain\Conversations\Models\Message;

/**
 * Инкапсулирует создание записи о сообщении в БД.
 * Можно замокать в тестах.
 */
final class MessageRecorder
{
    public function recordOutbound(
        int $conversationId,
        string $type,
        array $content,
        ?int $telegramMessageId,
    ): void {
        Message::create([
            'conversation_id'     => $conversationId,
            'direction'           => 'out',
            'type'                => $type,
            'content'             => $content,
            'telegram_message_id' => $telegramMessageId,
            'sent_at'             => now(),
        ]);
    }

    public function recordInbound(
        int $conversationId,
        string $type,
        array $content,
        ?int $telegramMessageId,
    ): void {
        Message::create([
            'conversation_id'     => $conversationId,
            'direction'           => 'in',
            'type'                => $type,
            'content'             => $content,
            'telegram_message_id' => $telegramMessageId,
            'sent_at'             => now(),
        ]);
    }
}
