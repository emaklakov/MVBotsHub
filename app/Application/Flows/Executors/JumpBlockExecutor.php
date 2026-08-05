<?php

declare(strict_types=1);

namespace App\Application\Flows\Executors;

use App\Domain\Flows\Contracts\BlockExecutorInterface;
use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;

final class JumpBlockExecutor implements BlockExecutorInterface
{
    public function supports(BlockType $type): bool
    {
        return $type === BlockType::JUMP;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        return new BlockExecutionResult(nextBlockId: $block['config']['target_block_id'] ?? null);
    }
}
