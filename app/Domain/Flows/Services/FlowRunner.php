<?php

namespace App\Domain\Flows\Services;

use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Flows\Models\FlowVersion;
use App\Jobs\Telegram\ProcessFlowStep;
use App\Jobs\Telegram\SendTelegramMessage;
use Illuminate\Support\Facades\Http;

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
    public function start(array $initialContext = []): void
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
            'context' => $initialContext,
            'status' => ConversationSessionStatus::ACTIVE,
            'expires_at' => now()->addHours(24), // Время активности сессии
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

    public function continueFromBlock(ConversationSession $session, string $blockId): void
    {
        $session->update(['current_block_id' => $blockId]);
        $this->processBlock($session);
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
            'input' => $this->handleInputBlock($session),
            'condition' => $this->handleConditionBlock($block, $session),
            'jump' => $this->handleJumpBlock($block, $session),
            'api_call' => $this->handleApiCallBlock($block, $session),
            'delay' => $this->handleDelayBlock($block, $session),
            'inline_keyboard' => $this->handleInlineKeyboardBlock($block, $session),
            default => $this->goNext($session, $block['next_id'] ?? null),
        };
    }

    protected function handleTextBlock(array $block, ConversationSession $session): void
    {
        $text = $this->resolveText($block['content'] ?? []);
        $this->sendText($text);
        $this->goNext($session, $block['next_id'] ?? null);
    }

    protected function handleButtonBlock(array $block, ConversationSession $session): void
    {
        $text = $this->resolveText($block['content'] ?? []);
        $buttons = $block['content']['buttons'] ?? [];
        $keyboard = array_map(fn(string $btn) => ['text' => $btn], $buttons);

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

    protected function handleConditionBlock(array $block, ConversationSession $session): void
    {
        $config = $block['config'] ?? [];
        $variable = $config['variable'] ?? '';
        $operator = $config['operator'] ?? 'eq';
        $value = $config['value'] ?? '';

        $contextValue = $session->context[$variable] ?? '';
        $result = false;

        switch ($operator) {
            case 'eq': $result = $contextValue == $value; break;
            case 'neq': $result = $contextValue != $value; break;
            case 'gt': $result = $contextValue > $value; break;
            case 'gte': $result = $contextValue >= $value; break;
            case 'lt': $result = $contextValue < $value; break;
            case 'lte': $result = $contextValue <= $value; break;
            case 'contains': $result = str_contains((string) $contextValue, (string) $value); break;
        }

        $branch = $result ? 'true' : 'false';
        $nextId = $block['branches'][$branch] ?? null;

        $this->goNext($session, $nextId);
    }

    protected function handleJumpBlock(array $block, ConversationSession $session): void
    {
        $targetId = $block['config']['target_block_id'] ?? null;
        $this->goNext($session, $targetId);
    }

    protected function handleApiCallBlock(array $block, ConversationSession $session): void
    {
        $config = $block['config'] ?? [];
        $url = $config['url'] ?? '';
        $method = strtoupper($config['method'] ?? 'GET');
        $headers = $config['headers'] ?? [];
        $variable = $config['variable'] ?? 'api_response';

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->{$method}($url);

            $result = $response->successful() ? $response->json() : ['error' => $response->body()];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage()];
        }

        $context = $session->context;
        $context[$variable] = $result;
        $session->context = $context;
        $session->save();

        $this->goNext($session, $block['next_id'] ?? null);
    }

    protected function handleDelayBlock(array $block, ConversationSession $session): void
    {
        $seconds = $block['config']['seconds'] ?? 0;
        $nextId = $block['next_id'] ?? null;

        if ($nextId) {
            ProcessFlowStep::dispatch($session->id, $nextId)
                ->delay(now()->addSeconds($seconds))
                ->onQueue('telegram');
        } else {
            $session->update(['status' => 'completed']);
        }
    }

    protected function handleInlineKeyboardBlock(array $block, ConversationSession $session): void
    {
        $text = $this->resolveText($block['content'] ?? []);
        $buttons = $block['config']['buttons'] ?? [];

        $inlineKeyboard = [];
        foreach ($buttons as $btn) {
            $inlineKeyboard[] = [
                [
                    'text' => $btn['text'],
                    'callback_data' => $btn['value'] ?? $btn['text'],
                ]
            ];
        }

        SendTelegramMessage::dispatch(
            $this->bot,
            $this->subscriber->telegram_id,
            $text,
            inlineKeyboard: $inlineKeyboard,
        )->onQueue('telegram');

        // Ждём callback — не двигаемся дальше
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
        if ($nextBlock && !in_array($nextBlock['type'], ['input', 'inline_keyboard'], true)) {
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

    protected function sendText(string $text): void
    {
        SendTelegramMessage::dispatch(
            $this->bot,
            $this->subscriber->telegram_id,
            $text,
        )->onQueue('telegram');
    }

    protected function resolveText(array $content): string
    {
        $language = $this->subscriber->effectiveLanguage;
        $text = $content['translations'][$language]
            ?? $content['translations']['ru']
            ?? $content['text']
            ?? '';

        // Подстановка переменных
        $session = ConversationSession::where('bot_subscriber_id', $this->subscriber->id)
            ->where('status', 'active')
            ->first();

        $variables = [];
        if ($session) {
            $variables = array_merge($variables, $session->context);
        }

        $variables['subscriber.telegram_id'] = $this->subscriber->telegram_id;
        $variables['subscriber.username'] = $this->subscriber->telegram_username ?? '';
        $variables['subscriber.language'] = $this->subscriber->effectiveLanguage;

        if ($this->subscriber->people) {
            $variables['people.phone'] = $this->subscriber->people->phone;
        }

        foreach ($variables as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace("{{{$key}}}", (string) $value, $text);
            }
        }

        // Убираем незаполненные плейсхолдеры
        $text = preg_replace('/\{\{[^}]+\}\}/', '', $text);

        return $text;
    }
}
