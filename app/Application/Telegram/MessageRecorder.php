<?php

namespace App\Application\Telegram;

use App\Domain\Conversations\Models\Message;
use App\Infrastructure\Telegram\DTO\Message as TelegramMessage;

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

    /**
     * @return array{0: string, 1: array}
     */
    public function extractContent(TelegramMessage $message): array
    {
        if ($photo = $message->photos()?->last()) {
            return ['photo', ['file_id' => $photo->id()]];
        }

        if ($document = $message->document()) {
            return ['document', ['file_id' => $document->id()]];
        }

        if ($voice = $message->voice()) {
            return ['voice', ['file_id' => $voice->id()]];
        }

        if ($contact = $message->contact()) {
            return ['contact', [
                'phone_number' => $contact->phoneNumber(),
                'first_name' => $contact->firstName(),
                'last_name' => $contact->lastName(),
            ]];
        }

        return ['text', ['text' => $message->text()]];
    }
}
