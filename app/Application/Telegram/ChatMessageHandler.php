<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Flows\Contracts\MessageSenderInterface;
use App\Infrastructure\Telegram\DTO\Message as TelegramMessage;
use Stringable;

final class ChatMessageHandler
{
    public function __construct(
        private readonly MessageSenderInterface $messageSender,
    ) {}

    public function handle(
        Bot $bot,
        BotSubscriber $subscriber,
        TelegramMessage $message,
        Stringable $text,
        int $conversationId,
        string $type,
        array $content
    ): void
    {
        if ($type === 'text') {
            $this->messageSender->send(new SendMessage(
                $bot,
                $subscriber->telegram_id,
                "Echo: {$content['text']}",
                $conversationId
            ));

            $this->messageSender->flush();
        }
    }
}
