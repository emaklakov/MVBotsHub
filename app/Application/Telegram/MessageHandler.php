<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Models\Bot;
use App\Infrastructure\Telegram\DTO\Message as TelegramMessage;
use Illuminate\Support\Str;

final class MessageHandler
{
    public function __construct(
        private readonly SubscriberResolver $subscriberResolver,
        private readonly ContactHandler $contactHandler,
        private readonly FlowSessionRunner $flowSessionRunner,
        private readonly FlowTriggerResolver $flowTriggerResolver,
        private readonly CommandHandler $commandHandler,
        private readonly ChatMessageHandler $chatMessageHandler,
    ) {}

    public function handle(Bot $bot, TelegramMessage $message): void
    {
        $telegramId = $message->from()?->id();
        if (!$telegramId) {
            return;
        }

        $subscriber = $this->subscriberResolver->resolve($bot, $telegramId, $message->from()?->username());
        $subscriber->update(['last_activity_at' => now()]);

        if ($contact = $message->contact()) {
            $this->contactHandler->handle($bot, $subscriber, $contact);
            return;
        }

        $textInput = $message->text();

        // 1. Активная сессия сценария — продолжаем
        if ($this->flowSessionRunner->hasActiveSession($subscriber)) {
            if ($textInput !== null) {
                $this->flowSessionRunner->handleInput($bot, $subscriber, $textInput);
            }
            return;
        }

        // 2. Триггер (/start, /command, deeplink)
        if ($textInput && str_starts_with($textInput, '/')) {
            $resolution = $this->flowTriggerResolver->resolve($bot, $textInput);

            if ($resolution !== null) {
                $this->flowSessionRunner->start(
                    $bot,
                    $subscriber,
                    $resolution->flowVersion,
                    $resolution->parameters
                );
                return;
            }
        }

        // 3. Команда бота
        $text = Str::of($message->text() ?? '');
        if ($this->commandHandler->isCommand($text)) {
            $this->commandHandler->handle($bot, $subscriber, $text);
            return;
        }

        // 4. Обычное сообщение
        $this->chatMessageHandler->handle($bot, $subscriber, $message, $text);
    }
}
