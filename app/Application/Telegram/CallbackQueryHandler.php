<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Contracts\TelegramGatewayInterface;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\ConversationSession;
use App\Infrastructure\Telegram\DTO\CallbackQuery as TelegramCallbackQuery;

final class CallbackQueryHandler
{
    public function __construct(
        private readonly FlowSessionRunner $flowSessionRunner,
        private readonly TelegramGatewayInterface $telegramGateway,
        private readonly SubscriberResolver $subscriberResolver,
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

        $session = ConversationSession::query()
            ->where('bot_subscriber_id', $subscriber->id)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->first();

        if (!$session) {
            return;
        }

        $data = $callbackQuery->data();
        $input = is_string($data) ? $data : (string) ($data->first() ?? '');

        $this->flowSessionRunner->handleInput(
            $bot,
            $subscriber,
            $input
        );
    }
}
