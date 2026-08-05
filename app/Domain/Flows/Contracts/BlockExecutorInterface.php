<?php

declare(strict_types=1);

namespace App\Domain\Flows\Contracts;

use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;

interface BlockExecutorInterface
{
    public function supports(BlockType $type): bool;

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult;
}
