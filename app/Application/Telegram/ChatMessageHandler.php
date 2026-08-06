<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Flows\Contracts\MessageSenderInterface;
use App\Infrastructure\Telegram\DTO\Message as TelegramMessage;
use Stringable;

final class ChatMessageHandler
{
    public function __construct(
        private readonly MessageSenderInterface $messageSender,
        private readonly MessageRecorder $messageRecorder,
    ) {}

    public function handle(
        Bot $bot,
        BotSubscriber $subscriber,
        TelegramMessage $message,
        Stringable $text
    ): void
    {
        $conversation = $this->resolveActiveConversation($bot, $subscriber);
        [$type, $content] = $this->extractContent($message);

        $this->messageRecorder->recordInbound(
            $conversation->id,
            $type,
            $content,
            $message->id()
        );

        if ($type === 'text') {
            $this->messageSender->send(new SendMessage(
                $bot,
                $subscriber->telegram_id,
                "Echo: {$content['text']}",
                $conversation->id
            ));
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

        return ['text', ['text' => $message->text()]];
    }
}
