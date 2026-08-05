<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Flows\Contracts\SessionStoreInterface;
use App\Domain\Flows\Entities\FlowSession;

final class EloquentSessionStore implements SessionStoreInterface
{
    public function findActive(int $subscriberId): ?FlowSession
    {
        $model = ConversationSession::query()
            ->where('bot_subscriber_id', $subscriberId)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->latest()
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function create(
        int $subscriberId,
        int $versionId,
        string $startGroupId,
        string $startBlockId,
        array $context
    ): FlowSession {
        $model = ConversationSession::create([
            'bot_subscriber_id' => $subscriberId,
            'flow_version_id' => $versionId,
            'current_group_id' => $startGroupId,
            'current_block_id' => $startBlockId,
            'context' => $context,
            'status' => ConversationSessionStatus::ACTIVE,
            'expires_at' => now()->addHours(24),
        ]);

        return $this->toEntity($model);
    }

    public function updatePosition(FlowSession $session, string $groupId, string $blockId): void
    {
        ConversationSession::query()->whereKey($session->id)->update([
            'current_group_id' => $groupId,
            'current_block_id' => $blockId,
        ]);

        $session->currentGroupId = $groupId;
        $session->currentBlockId = $blockId;
    }

    public function updateContext(FlowSession $session, array $context): void
    {
        ConversationSession::query()->whereKey($session->id)->update([
            'context' => $context,
        ]);

        $session->context = $context;
    }

    public function complete(int $sessionId): void
    {
        ConversationSession::query()->whereKey($sessionId)->update([
            'status' => ConversationSessionStatus::COMPLETED,
        ]);
    }

    public function completeAllActive(int $subscriberId): void
    {
        ConversationSession::query()
            ->where('bot_subscriber_id', $subscriberId)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->update(['status' => ConversationSessionStatus::COMPLETED]);
    }

    private function toEntity(ConversationSession $model): FlowSession
    {
        return new FlowSession(
            id: $model->id,
            botSubscriberId: $model->bot_subscriber_id,
            flowVersionId: $model->flow_version_id,
            currentGroupId: $model->current_group_id,
            currentBlockId: $model->current_block_id,
            context: $model->context ?? [],
            status: $model->status,
            expiresAt: $model->expires_at,
        );
    }
}
