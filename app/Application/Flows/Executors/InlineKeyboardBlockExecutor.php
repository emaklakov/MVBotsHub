<?php

declare(strict_types=1);

namespace App\Application\Flows\Executors;

use App\Domain\Flows\Contracts\BlockExecutorInterface;
use App\Domain\Flows\Contracts\MessengerInterface;
use App\Domain\Flows\Contracts\VariableResolverInterface;
use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;
use App\Domain\Flows\Enums\ExecutionStatus;

final class InlineKeyboardBlockExecutor implements BlockExecutorInterface
{
    public function __construct(
        private readonly MessengerInterface $messenger,
        private readonly VariableResolverInterface $variableResolver,
    ) {}

    public function supports(BlockType $type): bool
    {
        return $type === BlockType::INLINE_KEYBOARD;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        $language = $context->subscriber->effectiveLanguage;
        $raw = $block['content']['translations'][$language]
            ?? $block['content']['translations']['ru']
            ?? $block['content']['text']
            ?? '';

        $text = $this->variableResolver->resolve($raw, $context->session->context, $context->subscriber);

        $buttons = $block['config']['buttons'] ?? [];
        $inlineKeyboard = [];

        foreach ($buttons as $btn) {
            $inlineKeyboard[] = [[
                'text' => $btn['text'],
                'callback_data' => $btn['value'] ?? $btn['text'],
            ]];
        }

        $this->messenger->sendInlineKeyboard(
            $context->bot,
            $context->subscriber->telegram_id,
            $text,
            $inlineKeyboard
        );

        return new BlockExecutionResult(status: ExecutionStatus::WAITING);
    }
}
