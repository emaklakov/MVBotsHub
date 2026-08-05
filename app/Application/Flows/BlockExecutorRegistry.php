<?php

declare(strict_types=1);

namespace App\Application\Flows;

use App\Domain\Flows\Contracts\BlockExecutorInterface;
use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;

final class BlockExecutorRegistry
{
    /** @var BlockExecutorInterface[] */
    private array $executors = [];

    public function register(BlockExecutorInterface $executor): void
    {
        $this->executors[] = $executor;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        $type = BlockType::tryFrom($block['type'] ?? '');

        if (!$type) {
            return new BlockExecutionResult(nextBlockId: $block['next_id'] ?? null);
        }

        foreach ($this->executors as $executor) {
            if ($executor->supports($type)) {
                return $executor->execute($block, $context);
            }
        }

        return new BlockExecutionResult(nextBlockId: $block['next_id'] ?? null);
    }
}
