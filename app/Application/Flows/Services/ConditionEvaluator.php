<?php

declare(strict_types=1);

namespace App\Application\Flows\Services;

/**
 * Чистая функция. Не зависит от фреймворка.
 */
final class ConditionEvaluator
{
    public function evaluate(mixed $contextValue, string $operator, mixed $value): bool
    {
        return match ($operator) {
            'eq' => $contextValue == $value,
            'neq' => $contextValue != $value,
            'gt' => $contextValue > $value,
            'gte' => $contextValue >= $value,
            'lt' => $contextValue < $value,
            'lte' => $contextValue <= $value,
            'contains' => str_contains((string) $contextValue, (string) $value),
            default => false,
        };
    }
}
