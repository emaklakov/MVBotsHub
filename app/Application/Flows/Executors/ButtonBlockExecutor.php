<?php

declare(strict_types=1);

namespace App\Application\Flows\Executors;

use App\Domain\Flows\Contracts\BlockExecutorInterface;
use App\Domain\Flows\Contracts\MessengerInterface;
use App\Domain\Flows\Contracts\VariableResolverInterface;
use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;

final class ButtonBlockExecutor implements BlockExecutorInterface
{
    public function __construct(
        private readonly MessengerInterface $messenger,
        private readonly VariableResolverInterface $variableResolver,
    ) {}

    public function supports(BlockType $type): bool
    {
        return $type === BlockType::BUTTON;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        $content = $block['content'] ?? [];
        $language = $context->subscriber->effectiveLanguage;

        $raw = $content['translations'][$language]
            ?? $content['translations']['ru']
            ?? $content['text']
            ?? '';

        $text = $this->variableResolver->resolve($raw, $context->session->context, $context->subscriber);

        // flow-editor: buttons — массив строк
        $buttons = $content['buttons'] ?? [];
        $keyboard = array_map(fn(string $btn) => ['text' => $btn], $buttons);

        $this->messenger->sendText(
            $context->bot,
            $context->subscriber->telegram_id,
            $text,
            [
                'keyboard' => array_chunk($keyboard, 2),
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]
        );

        return new BlockExecutionResult();
    }

    private function resolveText(array $content, ExecutionContext $context): string
    {
        $language = $context->subscriber->effectiveLanguage;
        $raw = $content['translations'][$language]
            ?? $content['translations']['ru']
            ?? $content['text']
            ?? '';

        return $this->variableResolver->resolve($raw, $context->session->context, $context->subscriber);
    }
}
