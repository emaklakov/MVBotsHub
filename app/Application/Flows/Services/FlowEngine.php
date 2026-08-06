<?php

declare(strict_types=1);

namespace App\Application\Flows\Services;

use App\Application\Flows\BlockExecutorRegistry;
use App\Application\Flows\FlowSchemaNavigator;
use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Flows\Contracts\SessionStoreInterface;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Entities\FlowSession;
use App\Domain\Flows\Enums\ExecutionStatus;
use App\Domain\Flows\Models\FlowVersion;

final class FlowEngine
{
    private const int MAX_STEPS_PER_RUN = 200;

    public function __construct(
        private readonly SessionStoreInterface $sessionStore,
        private readonly BlockExecutorRegistry $registry,
    ) {}

    public function start(Bot $bot, BotSubscriber $subscriber, FlowVersion $version, array $initialContext = []): void
    {
        $this->sessionStore->completeAllActive($subscriber->id);

        $navigator = new FlowSchemaNavigator($version);
        $startGroupId = $navigator->getStartGroupId();

        if (!$startGroupId) {
            LogService::logWarning('Поток не имеет стартовой группы', ['version_id' => $version->id]);
            return;
        }

        $blocks = $navigator->getBlocksInGroup($startGroupId);
        $firstBlock = $blocks[0] ?? null;

        if (!$firstBlock) {
            LogService::logWarning('Стартовая группа пуста', ['version_id' => $version->id]);
            return;
        }

        $session = $this->sessionStore->create(
            $subscriber->id,
            $version->id,
            $startGroupId,
            $firstBlock['id'],
            $initialContext
        );

        $this->run($version, $session, $bot, $subscriber);
    }

    public function handleInput(Bot $bot, BotSubscriber $subscriber, FlowVersion $version, string $input): void
    {
        $session = $this->sessionStore->findActive($subscriber->id);

        if (!$session) {
            LogService::logInfo('Нет активной сессии для ввода', ['subscriber_id' => $subscriber->id]);
            return;
        }

        $navigator = new FlowSchemaNavigator($version);
        $currentBlock = $navigator->getBlock($session->currentBlockId);

        if (!$currentBlock || ($currentBlock['type'] ?? '') !== 'input') {
            return;
        }

        // Сохраняем ввод в контекст
        $variable = $currentBlock['config']['variable'] ?? 'input';
        $session->context[$variable] = $input;
        $this->sessionStore->updateContext($session, $session->context);

        // Переходим к следующему блоку
        $this->advance($version, $session, $bot, $subscriber);
    }

    private function run(FlowVersion $version, FlowSession $session, Bot $bot, BotSubscriber $subscriber): void
    {
        $steps = 0;

        while (true) {
            if (++$steps > self::MAX_STEPS_PER_RUN) {
                LogService::logWarning('Достигнут предельный лимит шагов потока', [
                    'session_id' => $session->id,
                    'steps' => $steps,
                ]);
                $this->sessionStore->complete($session->id);
                return;
            }

            $navigator = new FlowSchemaNavigator($version);
            $block = $navigator->getBlock($session->currentBlockId);

            if (!$block) {
                $this->sessionStore->complete($session->id);
                return;
            }

            $context = new ExecutionContext($session, $subscriber, $bot, $version);

            $result = $this->registry->execute($block, $context);

            if ($result->status === ExecutionStatus::WAITING) {
                return;
            }

            // Переходим дальше
            $advanced = $this->advanceToNext($version, $session, $navigator, $result->branch);

            if (!$advanced) {
                $this->sessionStore->complete($session->id);
                return;
            }
        }
    }

    private function advance(FlowVersion $version, FlowSession $session, Bot $bot, BotSubscriber $subscriber): void
    {
        $navigator = new FlowSchemaNavigator($version);
        $advanced = $this->advanceToNext($version, $session, $navigator);

        if (!$advanced) {
            $this->sessionStore->complete($session->id);
            return;
        }

        // Продолжаем выполнение
        $this->run($version, $session, $bot, $subscriber);
    }

    private function advanceToNext(FlowVersion $version, FlowSession $session, FlowSchemaNavigator $navigator, ?string $branch = null): bool
    {
        $currentGroupId = $session->currentGroupId;
        $currentBlockId = $session->currentBlockId;

        // 1. Пробуем найти следующий блок внутри текущей группы
        $nextBlock = $navigator->getNextBlockInGroup($currentGroupId, $currentBlockId);

        if ($nextBlock) {
            $this->sessionStore->updatePosition($session, $currentGroupId, $nextBlock['id']);
            return true;
        }

        // 2. Последний блок — ищем edge (с учётом branch для condition)
        $nextGroupId = $navigator->getNextGroupId($currentBlockId, $branch);

        if (!$nextGroupId) {
            return false; // Flow завершён
        }

        // 3. Переходим к первому блоку следующей группы
        $blocks = $navigator->getBlocksInGroup($nextGroupId);
        $firstBlock = $blocks[0] ?? null;

        if (!$firstBlock) {
            return false;
        }

        $this->sessionStore->updatePosition($session, $nextGroupId, $firstBlock['id']);
        return true;
    }

    public function continueFromBlock(
        Bot $bot,
        BotSubscriber $subscriber,
        FlowVersion $version,
        FlowSession $session,
        string $blockId
    ): void {
        $navigator = new FlowSchemaNavigator($version);
        $block = $navigator->getBlock($blockId);

        if (!$block) {
            $this->sessionStore->complete($session->id);
            return;
        }

        // Синхронизируем позицию сессии
        $this->sessionStore->updatePosition($session, $block['group_id'], $blockId);

        // Продолжаем выполнение с новой позиции
        $this->run($version, $session, $bot, $subscriber);
    }
}
