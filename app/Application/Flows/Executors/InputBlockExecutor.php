<?php

declare(strict_types=1);

namespace App\Application\Flows\Executors;

use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Flows\Contracts\BlockExecutorInterface;
use App\Domain\Flows\Contracts\MessageSenderInterface;
use App\Domain\Flows\Contracts\VariableResolverInterface;
use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;
use App\Domain\Flows\Enums\ExecutionStatus;

final class InputBlockExecutor implements BlockExecutorInterface
{
    public function __construct(
        private readonly MessageSenderInterface    $messageSender,
        private readonly VariableResolverInterface $variableResolver,
    ) {}

    public function supports(BlockType $type): bool
    {
        return in_array($type, [
            BlockType::INPUT,
            BlockType::NUMBER,
            BlockType::EMAIL,
            BlockType::PHONE,
            BlockType::DATE,
        ], true);
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

        $this->messageSender->send(new SendMessage($context->bot, $context->subscriber->telegram_id, $text, $context->conversationId));

        return new BlockExecutionResult(status: ExecutionStatus::WAITING);
    }
}
