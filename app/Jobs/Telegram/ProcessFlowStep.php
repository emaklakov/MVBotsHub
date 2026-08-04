<?php

namespace App\Jobs\Telegram;

use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Flows\Services\FlowRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Job для отложенных шагов (Delay)
class ProcessFlowStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $sessionId,
        public string $blockId,
    ) {}

    public function handle(): void
    {
        $session = ConversationSession::with(['subscriber', 'flowVersion.flow.bot'])
            ->find($this->sessionId);

        if (!$session || $session->status !== ConversationSessionStatus::ACTIVE) {
            return;
        }

        $session->update(['current_block_id' => $this->blockId]);

        $runner = new FlowRunner(
            $session->flowVersion->flow->bot,
            $session->subscriber,
            $session->flowVersion
        );

        $runner->continueFromBlock($session, $this->blockId);
    }
}
