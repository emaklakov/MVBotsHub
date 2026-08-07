<?php

declare(strict_types=1);

namespace App\Application\Flows\Services;

use App\Application\Flows\BlockExecutorRegistry;
use App\Application\Flows\FlowSchemaNavigator;
use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Flows\Contracts\MessageSenderInterface;
use App\Domain\Flows\Contracts\SessionStoreInterface;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Entities\FlowSession;
use App\Domain\Flows\Enums\ExecutionStatus;
use App\Domain\Flows\Models\FlowVersion;
use App\Infrastructure\Telegram\DTO\Contact as TelegramContact;

final class FlowEngine
{
    private const int MAX_STEPS_PER_RUN = 200;

    public function __construct(
        private readonly SessionStoreInterface $sessionStore,
        private readonly BlockExecutorRegistry $registry,
        private readonly MessageSenderInterface $messenger,
    ) {}

    public function start(Bot $bot, BotSubscriber $subscriber, FlowVersion $version, array $initialContext = [], ?int $conversationId = null): void
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

        $this->run($version, $session, $bot, $subscriber, $conversationId);
    }

    public function handleContact(Bot $bot, BotSubscriber $subscriber, FlowVersion $version, TelegramContact $contact, ?int $conversationId = null): void
    {
        $session = $this->sessionStore->findActive($subscriber->id);

        if (!$session) {
            LogService::logInfo('Нет активной сессии для контакта', ['subscriber_id' => $subscriber->id]);
            return;
        }

        $navigator = new FlowSchemaNavigator($version);
        $currentBlock = $navigator->getBlock($session->currentBlockId);

        // Обрабатываем контакт только если текущий блок ожидает его
        if (!$currentBlock || ($currentBlock['type'] ?? '') !== 'contact') {
            return;
        }

        // Определяем, под какой переменной хранить (из config.variable, fallback = 'contact_phone')
        $variable = $currentBlock['config']['variable'] ?? 'contact_phone';

        // Сохраняем телефон + дополнительные поля с префиксом по имени переменной
        $session->context[$variable]                 = $contact->phoneNumber();
        $session->context[$variable . '_first_name'] = $contact->firstName();
        $session->context[$variable . '_last_name']  = $contact->lastName() ?? '';

        $this->sessionStore->updateContext($session, $session->context);

        // Переходим к следующему блоку
        $this->advance($version, $session, $bot, $subscriber, $conversationId);
    }

    public function handleInput(Bot $bot, BotSubscriber $subscriber, FlowVersion $version, string $input, ?int $conversationId = null): void
    {
        $session = $this->sessionStore->findActive($subscriber->id);

        if (!$session) {
            LogService::logInfo('Нет активной сессии для ввода', ['subscriber_id' => $subscriber->id]);
            return;
        }

        $navigator = new FlowSchemaNavigator($version);
        $currentBlock = $navigator->getBlock($session->currentBlockId);

        $inputBlockTypes = ['input', 'number', 'email', 'phone', 'date', 'geolocation', 'contact'];
        if (!$currentBlock || !in_array($currentBlock['type'] ?? '', $inputBlockTypes, true)) {
            return;
        }

        // Сохраняем ввод в контекст
        $variable = $currentBlock['config']['variable'] ?? 'input';
        $session->context[$variable] = $input;
        $this->sessionStore->updateContext($session, $session->context);

        // Переходим к следующему блоку
        $this->advance($version, $session, $bot, $subscriber, $conversationId);
    }

    private function run(FlowVersion $version, FlowSession $session, Bot $bot, BotSubscriber $subscriber, ?int $conversationId = null): void
    {
        // flush() гарантированно вызывается один раз в конце прогона —
        // при любом исходе (WAITING/COMPLETED/лимит шагов/исключение),
        // чтобы всё, что накопил $this->messenger->send() внутри
        // executors, ушло одной строго упорядоченной Bus::chain-цепочкой.
        try {
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

                $context = new ExecutionContext($session, $subscriber, $bot, $version, $conversationId);

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
        } finally {
            $this->messenger->flush();
        }
    }

    private function advance(FlowVersion $version, FlowSession $session, Bot $bot, BotSubscriber $subscriber, ?int $conversationId = null): void
    {
        $navigator = new FlowSchemaNavigator($version);
        $advanced = $this->advanceToNext($version, $session, $navigator);

        if (!$advanced) {
            $this->sessionStore->complete($session->id);
            return;
        }

        // Продолжаем выполнение
        $this->run($version, $session, $bot, $subscriber, $conversationId);
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
        $this->run($version, $session, $bot, $subscriber, null); // We don't have conversationId here, it's called from where?
    }
}
