<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Contracts\TelegramGatewayInterface;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\ConversationSession;
use App\Infrastructure\Telegram\DTO\TelegramCallbackQuery;

final class CallbackQueryHandler
{
    public function __construct(
        private readonly FlowSessionRunner $flowSessionRunner,
        private readonly TelegramGatewayInterface $telegramGateway,
    ) {}

    public function handle(Bot $bot, array $callbackPayload): void
    {
        $callbackQuery = TelegramCallbackQuery::fromArray($callbackPayload);

        $telegramId = $callbackQuery->from()?->id();
        if (!$telegramId) {
            return;
        }

        // Убираем "часики" на кнопке
        $this->telegramGateway->answerCallbackQuery($bot, $callbackQuery->id());

        $subscriber = BotSubscriber::query()
            ->where('bot_id', $bot->id)
            ->where('telegram_id', $telegramId)
            ->first();

        if (!$subscriber) {
            return;
        }

        $session = ConversationSession::query()
            ->where('bot_subscriber_id', $subscriber->id)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->first();

        if (!$session) {
            return;
        }

        $this->flowSessionRunner->handleInput(
            $bot,
            $subscriber,
            $callbackQuery->data() ?? ''
        );
    }
}
