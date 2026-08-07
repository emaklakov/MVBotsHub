<?php

declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Application\Flows\FlowSchemaNavigator;
use App\Application\Flows\Services\FlowEngine;
use App\Application\Services\LogService;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Flows\Entities\FlowSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * Job для продолжения flow после задержки (Delay).
 *
 * botId/chatId передаются явно при диспатче (см. DelayBlockExecutor), а не
 * восстанавливаются из sessionId внутри handle() — они нужны ДО загрузки
 * сессии, чтобы построить ключ WithoutOverlapping и не потерять привязку
 * к чату, если сессия к моменту выполнения джобы уже изменится/протухнет.
 */
final class ProcessFlowStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $sessionId,
        public string $blockId,
        public int $botId,
        public int $chatId,
    ) {}

    /**
     * Гарантирует, что продолжение flow после Delay не выполнится параллельно
     * с другой джобой (SendTelegramMessage, ещё одним ProcessFlowStep и т.д.),
     * которая в этот же момент отправляет сообщение этому же chat_id.
     * Иначе порядок доставки сообщений в чат не гарантирован.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("telegram-chat:{$this->botId}:{$this->chatId}"))
                ->releaseAfter(2)
                ->expireAfter(180),
        ];
    }

    public function handle(FlowEngine $flowEngine): void
    {
        $session = ConversationSession::with(['subscriber', 'flowVersion.flow.bot'])
            ->find($this->sessionId);

        if (!$session || $session->status !== ConversationSessionStatus::ACTIVE) {
            return;
        }

        // Защита от рассинхрона: если к моменту выполнения джобы бот/чат сессии
        // не совпадает с тем, под каким chat_id мы держали WithoutOverlapping-лок,
        // лучше пропустить шаг, чем продолжить flow вне гарантии порядка доставки.
        if ($session->flowVersion->flow->bot_id !== $this->botId
            || $session->subscriber->telegram_id !== $this->chatId
        ) {
            LogService::logWarning('ProcessFlowStep: bot/chat сессии не совпадает с ключом джобы', [
                'session_id' => $this->sessionId,
                'expected_bot_id' => $this->botId,
                'expected_chat_id' => $this->chatId,
            ]);
            return;
        }

        $flowSession = new FlowSession(
            id: $session->id,
            botSubscriberId: $session->bot_subscriber_id,
            flowVersionId: $session->flow_version_id,
            currentGroupId: $session->current_group_id,
            currentBlockId: $session->current_block_id,
            context: $session->context ?? [],
            status: $session->status,
            expiresAt: $session->expires_at,
        );

        // Продолжаем выполнение с новой позиции
        $flowEngine->continueFromBlock(
            $session->flowVersion->flow->bot,
            $session->subscriber,
            $session->flowVersion,
            $flowSession,
            $this->blockId
        );
    }
}
