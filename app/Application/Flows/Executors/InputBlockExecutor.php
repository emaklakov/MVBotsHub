<?php

declare(strict_types=1);

namespace App\Application\Flows\Executors;

use App\Domain\Flows\Contracts\BlockExecutorInterface;
use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;
use App\Domain\Flows\Enums\ExecutionStatus;

final class InputBlockExecutor implements BlockExecutorInterface
{
    public function supports(BlockType $type): bool
    {
        return $type === BlockType::INPUT;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        $content = $block['content'] ?? [];
        $language = $context->subscriber->effectiveLanguage;

        // Вопрос перед вводом
        $raw = $content['translations'][$language]
            ?? $content['translations']['ru']
            ?? $content['text']
            ?? '';

        $text = $this->variableResolver->resolve($raw, $context->session->context, $context->subscriber);

        $this->messenger->sendText($context->bot, $context->subscriber->telegram_id, $text);

        return new BlockExecutionResult(status: ExecutionStatus::WAITING);
    }
}
