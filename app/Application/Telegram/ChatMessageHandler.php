<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Conversations\Models\Message as MessageModel;
use App\Infrastructure\Telegram\DTO\TelegramMessage;
use Stringable;

final class ChatMessageHandler
{
    public function __construct(
        private readonly TelegramMessageSender $messageSender,
    ) {}

    public function handle(Bot $bot, BotSubscriber $subscriber, TelegramMessage $message, Stringable $text): void
    {
        $conversation = $this->resolveActiveConversation($bot, $subscriber);

        [$type, $content] = $this->extractContent($message);

        MessageModel::create([
            'conversation_id'     => $conversation->id,
            'direction'           => 'in',
            'type'                => $type,
            'content'             => $content,
            'telegram_message_id' => $message->id(),
        ]);

        if ($type === 'text') {
            $this->messageSender->send(
                $bot,
                $subscriber->telegram_id,
                "Echo: {$content['text']}",
                $conversation->id
            );
        }
    }

    private function resolveActiveConversation(Bot $bot, BotSubscriber $subscriber): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'bot_subscriber_id' => $subscriber->id,
                'status'            => ConversationStatus::ACTIVE,
            ],
            [
                'bot_id'  => $bot->id,
                'context' => [],
            ]
        );
    }

    /**
     * @return array{0: string, 1: array}
     */
    private function extractContent(TelegramMessage $message): array
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

        return ['text', ['text' => (string) $message->text()]];
    }
}
