<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Contracts\TelegramGatewayInterface;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\BotSubscriberStatus;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Conversations\Models\ConversationSession;
use App\Infrastructure\Telegram\DTO\CallbackQuery as TelegramCallbackQuery;

final class CallbackQueryHandler
{
    public function __construct(
        private readonly FlowSessionRunner $flowSessionRunner,
        private readonly TelegramGatewayInterface $telegramGateway,
        private readonly SubscriberResolver $subscriberResolver,
        private readonly MessageRecorder $messageRecorder,
    ) {}

    public function handle(Bot $bot, TelegramCallbackQuery $callbackQuery): void
    {
        $telegramId = $callbackQuery->from()?->id();
        if (!$telegramId) {
            return;
        }

        // Убираем "часики" на кнопке
        $this->telegramGateway->answerCallbackQuery($bot, $callbackQuery->id());

        $subscriber = $this->subscriberResolver->resolve($bot, $callbackQuery->from());

        if($subscriber->status != BotSubscriberStatus::ACTIVE) {
            return;
        }

        $session = ConversationSession::query()
            ->where('bot_subscriber_id', $subscriber->id)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->first();

        if (!$session) {
            return;
        }

        $data = $callbackQuery->data();
        $input = is_string($data) ? $data : (string) ($data->first() ?? '');

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

        $this->messageRecorder->recordInbound(
            $conversation->id,
            'text',
            ['text' => $input],
            $callbackQuery->message()?->id()
        );

        $this->flowSessionRunner->handleInput(
            $bot,
            $subscriber,
            $input,
            $conversation->id
        );
    }
}
