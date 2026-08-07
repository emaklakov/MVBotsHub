<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Flows\Services\FlowEngine;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Flows\Contracts\SessionStoreInterface;
use App\Domain\Flows\Models\FlowVersion;
use App\Infrastructure\Telegram\DTO\Contact as TelegramContact;

/**
 * Обертка над FlowRunner с DI.
 * Изолирует Job от прямого создания FlowRunner.
 */
final class FlowSessionRunner
{
    public function __construct(
        private readonly FlowEngine $flowEngine,
        private readonly SessionStoreInterface $sessionStore,
    ) {}

    public function hasActiveSession(BotSubscriber $subscriber): bool
    {
        return ConversationSession::query()
            ->where('bot_subscriber_id', $subscriber->id)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->exists();
    }

    public function handleInput(Bot $bot, BotSubscriber $subscriber, string $input, ?int $conversationId = null): void
    {
        $session = $this->sessionStore->findActive($subscriber->id);

        if (!$session) return;

        // Получаем FlowVersion из сессии
        $version = FlowVersion::find($session->flowVersionId);
        if (!$version) return;

        $this->flowEngine->handleInput($bot, $subscriber, $version, $input, $conversationId);
    }

    public function handleContact(Bot $bot, BotSubscriber $subscriber, TelegramContact $contact, ?int $conversationId = null): void
    {
        $session = $this->sessionStore->findActive($subscriber->id);

        if (!$session) return;

        $version = FlowVersion::find($session->flowVersionId);
        if (!$version) return;

        $this->flowEngine->handleContact($bot, $subscriber, $version, $contact, $conversationId);
    }

    public function start(Bot $bot, BotSubscriber $subscriber, FlowVersion $version, array $context = [], ?int $conversationId = null): void
    {
        $this->flowEngine->start($bot, $subscriber, $version, $context, $conversationId);
    }
}
