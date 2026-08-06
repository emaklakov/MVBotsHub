<?php

declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Application\Flows\FlowSchemaNavigator;
use App\Application\Flows\Services\FlowEngine;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Flows\Entities\FlowSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job для продолжения flow после задержки (Delay).
 */
final class ProcessFlowStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $sessionId,
        public string $blockId,
    ) {}

    public function handle(FlowEngine $flowEngine): void
    {
        $session = ConversationSession::with(['subscriber', 'flowVersion.flow.bot'])
            ->find($this->sessionId);

        if (!$session || $session->status !== ConversationSessionStatus::ACTIVE) {
            return;
        }

        $flowSession = new FlowSession(
            id: $session->id,
            botSubscriberId: $session->bot_subscriber_id,
            flowVersionId: $session->flow_version_id,
            currentGroupId: $session->current_group_id,
            currentBlockId: $session->current_block_id,
            context: $session->context ?? [],
            status: $session->status,
            expiresAt: $session->expires_at,
        );

        // Продолжаем выполнение с новой позиции
        $flowEngine->continueFromBlock(
            $session->flowVersion->flow->bot,
            $session->subscriber,
            $session->flowVersion,
            $flowSession,
            $this->blockId
        );
    }
}
