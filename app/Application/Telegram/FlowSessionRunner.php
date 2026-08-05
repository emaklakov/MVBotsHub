<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Flows\Models\FlowVersion;
use App\Domain\Flows\Services\FlowRunner;

/**
 * Обертка над FlowRunner с DI.
 * Изолирует Job от прямого создания FlowRunner.
 */
final class FlowSessionRunner
{
    public function __construct(
        private readonly FlowRunner $flowRunner, // Теперь FlowRunner — сервис в DI-контейнере
    ) {}

    public function hasActiveSession(BotSubscriber $subscriber): bool
    {
        return ConversationSession::query()
            ->where('bot_subscriber_id', $subscriber->id)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->exists();
    }

    public function handleInput(Bot $bot, BotSubscriber $subscriber, string $input): void
    {
        $session = ConversationSession::query()
            ->where('bot_subscriber_id', $subscriber->id)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->first();

        if (!$session) {
            return;
        }

        $this->flowRunner
            ->forBot($bot)
            ->forSubscriber($subscriber)
            ->forVersion($session->flowVersion)
            ->handleInput($input);
    }

    public function start(Bot $bot, BotSubscriber $subscriber, FlowVersion $version, array $context = []): void
    {
        $this->flowRunner
            ->forBot($bot)
            ->forSubscriber($subscriber)
            ->forVersion($version)
            ->start($context);
    }
}
