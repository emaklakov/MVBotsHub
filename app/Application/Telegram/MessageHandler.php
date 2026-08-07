<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\BotSubscriberStatus;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Models\Conversation;
use App\Infrastructure\Telegram\DTO\Contact as TelegramContact;
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
        private readonly MessageRecorder $messageRecorder,
    ) {}

    public function handle(Bot $bot, TelegramMessage $message): void
    {
        $from = $message->from();
        if (!$from) {
            return;
        }

        // Заполняем данные по пользователю
        $subscriber = $this->subscriberResolver->resolve($bot, $from);

        if($subscriber->status != BotSubscriberStatus::ACTIVE) {
            return;
        }

        $conversation = Conversation::firstOrCreate(
            [
                'bot_subscriber_id' => $subscriber->id,
                'status'            => ConversationStatus::ACTIVE,
            ],
            [
                'bot_id'  => $bot->id,
                'context' => [],
            ]
        );

        [$type, $content] = $this->messageRecorder->extractContent($message);

        $this->messageRecorder->recordInbound(
            $conversation->id,
            $type,
            $content,
            $message->id()
        );

        $contact = $message->contact();
        $textInput = $message->text();

        // 1. Активная сессия сценария — продолжаем.
        //    Контакт внутри сессии уходит во Flow (contact-блок), а не в ContactHandler.
        if ($this->flowSessionRunner->hasActiveSession($subscriber)) {
            if ($contact !== null) {
                $this->flowSessionRunner->handleContact($bot, $subscriber, $contact, $conversation->id);
            } elseif ($textInput !== null) {
                $this->flowSessionRunner->handleInput($bot, $subscriber, $textInput, $conversation->id);
            }
            return;
        }

        // 2. Контакт вне сессии — регистрационный сценарий
        if ($contact !== null) {
            $this->contactHandler->handle($bot, $subscriber, $contact);
            return;
        }

        // 3. Триггер (/start, /command, deeplink)
        if ($textInput && str_starts_with($textInput, '/')) {
            $resolution = $this->flowTriggerResolver->resolve($bot, $textInput);

            if ($resolution !== null) {
                $this->flowSessionRunner->start(
                    $bot,
                    $subscriber,
                    $resolution->flowVersion,
                    $resolution->parameters,
                    $conversation->id
                );
                return;
            }
        }

        // 4. Команда бота
        $text = Str::of($message->text() ?? '');
        if ($this->commandHandler->isCommand($text)) {
            $this->commandHandler->handle($bot, $subscriber, $text);
            return;
        }

        // 5. Обычное сообщение
        $this->chatMessageHandler->handle($bot, $subscriber, $message, $text, $conversation->id, $type, $content);
    }
}
