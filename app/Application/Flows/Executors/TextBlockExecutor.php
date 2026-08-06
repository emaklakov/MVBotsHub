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

final class TextBlockExecutor implements BlockExecutorInterface
{
    public function __construct(
        private readonly MessageSenderInterface    $messenger,
        private readonly VariableResolverInterface $variableResolver,
    ) {}

    public function supports(BlockType $type): bool
    {
        return $type === BlockType::TEXT;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        $content = $block['content'] ?? [];
        $language = $context->subscriber->effectiveLanguage;

        // flow-editor хранит переводы в translations, fallback на text
        $raw = $content['translations'][$language]
            ?? $content['translations']['ru']
            ?? $content['text']
            ?? '';

        $text = $this->variableResolver->resolve($raw, $context->session->context, $context->subscriber);

        $this->messenger->send(new SendMessage($context->bot, $context->subscriber->telegram_id, $text));

        return new BlockExecutionResult(); // CONTINUE по умолчанию
    }
}
