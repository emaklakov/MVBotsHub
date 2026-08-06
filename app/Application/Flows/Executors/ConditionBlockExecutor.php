<?php

declare(strict_types=1);

namespace App\Application\Flows\Executors;

use App\Application\Flows\Services\ConditionEvaluator;
use App\Domain\Flows\Contracts\BlockExecutorInterface;
use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;

final class ConditionBlockExecutor implements BlockExecutorInterface
{
    public function __construct(
        private readonly ConditionEvaluator $evaluator,
    ) {}

    public function supports(BlockType $type): bool
    {
        return $type === BlockType::CONDITION;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        $config = $block['config'] ?? [];
        $variable = $config['variable'] ?? '';
        $operator = $config['operator'] ?? 'eq';
        $value = $config['value'] ?? '';

        $contextValue = $context->session->context[$variable] ?? '';
        $result = $this->evaluator->evaluate($contextValue, $operator, $value);
        $branch = $result ? 'true' : 'false';

        return new BlockExecutionResult(nextBlockId: $block['branches'][$branch] ?? null);
    }
}
