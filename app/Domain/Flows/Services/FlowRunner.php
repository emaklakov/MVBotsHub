<?php

namespace App\Domain\Flows\Services;

use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Flows\Models\FlowVersion;
use App\Jobs\Telegram\SendTelegramMessage;

class FlowRunner
{
    public function __construct(
        protected Bot $bot,
        protected BotSubscriber $subscriber,
        protected FlowVersion $version,
    ) {}

    /**
     * Запуск нового сценария
     */
    public function start(): void
    {
        // Закрываем старые сессии
        ConversationSession::where('bot_subscriber_id', $this->subscriber->id)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->update(['status' => ConversationSessionStatus::COMPLETED]);

        $startBlockId = $this->version->getStartBlockId();

        if (!$startBlockId) {
            LogService::logWarning('Поток не имеет начального блока', ['version_id' => $this->version->id]);
            return;
        }

        $session = ConversationSession::create([
            'bot_subscriber_id' => $this->subscriber->id,
            'flow_version_id' => $this->version->id,
            'current_block_id' => $startBlockId,
            'context' => [],
            'status' => ConversationSessionStatus::ACTIVE,
            'expires_at' => now()->addHours(24),
        ]);

        $this->processBlock($session);
    }

    /**
     * Обработка ввода пользователя в рамках активной сессии
     */
    public function handleInput(string $input, array $payload = []): void
    {
        $session = ConversationSession::where('bot_subscriber_id', $this->subscriber->id)
            ->where('status', ConversationSessionStatus::ACTIVE)
            ->latest()
            ->first();

        if (!$session) {
            LogService::logInfo('Нет активной сессии для ввода', [
                'subscriber_id' => $this->subscriber->id,
            ]);
            return;
        }

        $block = $this->version->getBlock($session->current_block_id);

        if (!$block) {
            $session->update(['status' => ConversationSessionStatus::COMPLETED]);
            return;
        }

        // Сохраняем ввод в контекст
        if ($block['type'] === 'input') {
            $variable = $block['config']['variable'] ?? 'input';
            $context = $session->context;
            $context[$variable] = $input;
            $session->context = $context;
            $session->save();
        }

        // Переходим дальше
        $this->goNext($session, $block['next_id'] ?? null);
    }

    /**
     * Выполнение блока
     */
    protected function processBlock(ConversationSession $session): void
    {
        $block = $this->version->getBlock($session->current_block_id);

        if (!$block) {
            $session->update(['status' => ConversationSessionStatus::COMPLETED]);
            return;
        }

        match ($block['type']) {
            'text' => $this->handleTextBlock($block, $session),
            'button' => $this->handleButtonBlock($block, $session),
            'input' => $this->handleInputBlock($session), // ждём ввода
            default => $this->goNext($session, $block['next_id'] ?? null),
        };
    }

    protected function handleTextBlock(array $block, ConversationSession $session): void
    {
        $text = $this->resolveText($block['content'] ?? []);

        SendTelegramMessage::dispatch(
            $this->bot,
            $this->subscriber->telegram_id,
            $text,
        )->onQueue('telegram');

        $this->goNext($session, $block['next_id'] ?? null);
    }

    protected function handleButtonBlock(array $block, ConversationSession $session): void
    {
        $text = $this->resolveText($block['content'] ?? []);
        $buttons = $block['content']['buttons'] ?? [];

        $keyboard = array_map(
            fn(string $btn) => ['text' => $btn],
            $buttons
        );

        SendTelegramMessage::dispatch(
            $this->bot,
            $this->subscriber->telegram_id,
            $text,
            replyMarkup: [
                'keyboard' => array_chunk($keyboard, 2),
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]
        )->onQueue('telegram');

        $this->goNext($session, $block['next_id'] ?? null);
    }

    protected function handleInputBlock(ConversationSession $session): void
    {
        // Сессия уже стоит на этом блоке — ждём ввода пользователя
        // Ничего не делаем
    }

    protected function goNext(ConversationSession $session, ?string $nextId): void
    {
        if (!$nextId) {
            $session->update(['status' => ConversationSessionStatus::COMPLETED]);
            return;
        }

        $session->update(['current_block_id' => $nextId]);

        $nextBlock = $this->version->getBlock($nextId);

        // Если следующий блок не требует ввода — обрабатываем рекурсивно
        if ($nextBlock && !in_array($nextBlock['type'], ['input'], true)) {
            // Защита от бесконечной рекурсии — ограничим 10 шагов
            static $depth = 0;
            if (++$depth > 10) {
                LogService::logWarning('Достигнут предельный уровень глубины потока', ['session_id' => $session->id]);
                $session->update(['status' => ConversationSessionStatus::COMPLETED]);
                return;
            }
            $this->processBlock($session);
            --$depth;
        }
    }

    protected function resolveText(array $content): string
    {
        $language = $this->subscriber->effectiveLanguage;

        // Переводы имеют приоритет
        if (!empty($content['translations'][$language])) {
            return $content['translations'][$language];
        }

        // Fallback на русский или raw text
        return $content['translations']['ru']
            ?? $content['text']
            ?? '';
    }
}
