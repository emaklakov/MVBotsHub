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

        $variable = $config['conditionVariable'] ?? '';
        $operator = $config['conditionOperator'] ?? '==';
        $value = $config['conditionValue'] ?? '';

        $contextValue = $context->session->context[$variable] ?? null;

        $result = match ($operator) {
            'is_set' => $contextValue !== null && $contextValue !== '',
            'is_empty' => $contextValue === null || $contextValue === '',
            default => $this->evaluator->evaluate(
                $contextValue ?? '',
                $this->mapOperator($operator),
                $value
            ),
        };

        return new BlockExecutionResult(branch: $result ? 'true' : 'false');
    }

    private function mapOperator(string $operator): string
    {
        return match ($operator) {
            '==' => 'eq',
            '!=' => 'neq',
            'contains' => 'contains',
            default => 'eq',
        };
    }
}
