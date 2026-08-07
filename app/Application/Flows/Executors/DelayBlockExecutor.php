<?php

declare(strict_types=1);

namespace App\Application\Flows\Executors;

use App\Domain\Flows\Contracts\BlockExecutorInterface;
use App\Domain\Flows\Contracts\SessionStoreInterface;
use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;
use App\Domain\Flows\Enums\ExecutionStatus;
use App\Jobs\Telegram\ProcessFlowStep;

final class DelayBlockExecutor implements BlockExecutorInterface
{
    public function __construct(
        private readonly SessionStoreInterface $sessionStore,
    ) {}

    public function supports(BlockType $type): bool
    {
        return $type === BlockType::DELAY;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        $seconds = $block['config']['seconds'] ?? 0;
        $nextId = $block['next_id'] ?? null;

        if ($nextId) {
            ProcessFlowStep::dispatch(
                $context->session->id,
                $nextId,
                $context->bot->id,
                $context->subscriber->telegram_id,
            )
                ->delay(now()->addSeconds($seconds))
                ->onQueue('telegram');
        } else {
            $this->sessionStore->complete($context->session->id);
        }

        return new BlockExecutionResult(status: ExecutionStatus::WAITING);
    }
}
