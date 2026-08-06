<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Flows\Services\FlowEngine;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Flows\Models\FlowVersion;

/**
 * Обертка над FlowRunner с DI.
 * Изолирует Job от прямого создания FlowRunner.
 */
final class FlowSessionRunner
{
    public function __construct(
        private readonly FlowEngine $flowEngine,
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
        $session = $this->sessionStore->findActive($subscriber->id);

        if (!$session) return;

        // Получаем FlowVersion из сессии
        $version = FlowVersion::find($session->flowVersionId);
        if (!$version) return;

        $this->flowEngine->handleInput($bot, $subscriber, $version, $input);
    }

    public function start(Bot $bot, BotSubscriber $subscriber, FlowVersion $version, array $context = []): void
    {
        $this->flowEngine->start($bot, $subscriber, $version, $context);
    }
}
