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

final class ButtonBlockExecutor implements BlockExecutorInterface
{
    public function __construct(
        private readonly MessageSenderInterface    $messenger,
        private readonly VariableResolverInterface $variableResolver,
    ) {}

    public function supports(BlockType $type): bool
    {
        return $type === BlockType::BUTTON;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        $content = $block['content'] ?? [];
        $config = $block['config'] ?? [];
        $language = $context->subscriber->effectiveLanguage;

        $raw = $content['translations'][$language]
            ?? $content['translations']['ru']
            ?? $content['text']
            ?? '';

        $text = $this->variableResolver->resolve($raw, $context->session->context, $context->subscriber);

        $keyboardMode = $config['keyboardMode'] ?? 'reply';

        if ($keyboardMode === 'inline') {
            $this->sendInline($text, $content['buttons'] ?? [], $context);
            return new BlockExecutionResult(status: ExecutionStatus::WAITING);
        }

        $this->sendReply($text, $content['buttons'] ?? [], $context);
        return new BlockExecutionResult();
    }

    private function sendReply(string $text, array $buttons, ExecutionContext $context): void
    {
        $keyboard = array_map(fn(string $btn) => ['text' => $btn], $buttons);

        $this->messenger->send(new SendMessage(
            $context->bot,
            $context->subscriber->telegram_id,
            $text,
            replyMarkup: array_chunk($keyboard, 2) // ← просто массив рядов, без обёртки
        ));
    }

    private function sendInline(string $text, array $buttons, ExecutionContext $context): void
    {
        $inlineKeyboard = [];
        foreach ($buttons as $btn) {
            $inlineKeyboard[] = [[
                'text' => $btn,
                'callback_data' => $btn,
            ]];
        }

        $this->messenger->send(new SendMessage(
            $context->bot,
            $context->subscriber->telegram_id,
            $text,
            inlineKeyboard: $inlineKeyboard
        ));
    }
}
