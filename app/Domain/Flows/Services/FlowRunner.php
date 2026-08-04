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
    /**
     * Специальное значение, которое возвращают обработчики блоков,
     * ожидающих внешнее событие (ввод пользователя, нажатие inline-кнопки,
     * срабатывание отложенного шага). При его получении run() прекращает
     * цикл, ничего не завершая — сессия остаётся ACTIVE на текущем блоке.
     */
    private const string WAITING = '__waiting__';

    /**
     * Защита от бесконечного цикла в самом графе сценария (например,
     * jump -> jump -> jump из-за ошибки в схеме). Это НЕ ограничение на
     * длину нормального сценария с условиями/переходами без ввода —
     * такие сценарии могут спокойно делать десятки шагов за один проход.
     * Раньше лимит (10) был жёстко завязан на recursion-depth и хранился
     * в static-переменной метода — из-за этого он «протекал» между
     * разными сессиями/пользователями в рамках одного воркера очереди,
     * если где-то по пути вылетало исключение. Теперь это обычная
     * локальная переменная внутри одного вызова run() — никакого
     * межпроцессного/межсессионного состояния.
     */
    private const int MAX_STEPS_PER_RUN = 200;

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

        $this->run($session, $startBlockId);
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

        $this->run($session, $block['next_id'] ?? null);
    }

    /**
     * Продолжение сценария после отложенного шага (delay-блок)
     */
    public function continueFromBlock(ConversationSession $session, string $blockId): void
    {
        $this->run($session, $blockId);
    }

    /**
     * Главный цикл выполнения потока.
     *
     * Раньше шаги выполнялись рекурсивным вызовом processBlock() ->
     * goNext() -> processBlock() с ограничением глубины через static-
     * переменную внутри метода. Проблема: static-переменная общая на весь
     * процесс воркера очереди (Horizon-воркер живёт часами и обрабатывает
     * множество джобов подряд), а декремент счётчика не выполнялся, если
     * где-то по пути вылетало исключение (--$depth стоял после
     * рекурсивного вызова без try/finally). Один упавший блок у одного
     * пользователя мог навсегда испортить счётчик для всех последующих
     * сессий, обрабатываемых этим же воркером.
     *
     * Теперь это простой while-цикл без рекурсии: $steps — обычная
     * локальная переменная, живёт только в рамках одного вызова run() и
     * не переживает исключения за пределами метода.
     */
    protected function run(ConversationSession $session, ?string $blockId): void
    {
        $steps = 0;

        while ($blockId !== null) {
            if (++$steps > self::MAX_STEPS_PER_RUN) {
                LogService::logWarning('Достигнут предельный лимит шагов потока за один запуск', [
                    'session_id' => $session->id,
                    'steps' => $steps,
                ]);
                $session->update(['status' => ConversationSessionStatus::COMPLETED]);
                return;
            }

            $block = $this->version->getBlock($blockId);

            if (!$block) {
                $session->update(['status' => ConversationSessionStatus::COMPLETED]);
                return;
            }

            $session->update(['current_block_id' => $blockId]);

            $next = $this->executeBlock($block, $session);

            if ($next === self::WAITING) {
                // Блок сам решил, что дальше двигаться не нужно прямо
                // сейчас (ждём ввод, callback или сработавший delay-джоб).
                // current_block_id уже сохранён выше — сессия остаётся ACTIVE.
                return;
            }

            $blockId = $next;
        }

        // Дошли до блока без next_id — сценарий завершён штатно.
        $session->update(['status' => ConversationSessionStatus::COMPLETED]);
    }

    /**
     * Выполнение одного блока.
     *
     * @return string|null Идентификатор следующего блока, null — если
     *                      сценарий на этом завершается, либо WAITING —
     *                      если нужно приостановиться и ждать внешнее событие.
     */
    protected function executeBlock(array $block, ConversationSession $session): ?string
    {
        return match ($block['type']) {
            'text' => $this->handleTextBlock($block, $session),
            'button' => $this->handleButtonBlock($block, $session),
            'input' => self::WAITING, // сессия уже стоит на этом блоке — ждём ввод пользователя
            'condition' => $this->handleConditionBlock($block, $session),
            'jump' => $this->handleJumpBlock($block),
            'api_call' => $this->handleApiCallBlock($block, $session),
            'delay' => $this->handleDelayBlock($block, $session),
            'inline_keyboard' => $this->handleInlineKeyboardBlock($block, $session),
            default => $block['next_id'] ?? null,
        };
    }

    protected function handleTextBlock(array $block, ConversationSession $session): ?string
    {
        $text = $this->resolveText($block['content'] ?? [], $session);
        $this->sendText($text);

        return $block['next_id'] ?? null;
    }

    protected function handleButtonBlock(array $block, ConversationSession $session): ?string
    {
        $text = $this->resolveText($block['content'] ?? [], $session);
        $buttons = $block['content']['buttons'] ?? [];
        $keyboard = array_map(fn (string $btn) => ['text' => $btn], $buttons);

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

        return $block['next_id'] ?? null;
    }

    protected function handleConditionBlock(array $block, ConversationSession $session): ?string
    {
        $config = $block['config'] ?? [];
        $variable = $config['variable'] ?? '';
        $operator = $config['operator'] ?? 'eq';
        $value = $config['value'] ?? '';

        $contextValue = $session->context[$variable] ?? '';

        $result = match ($operator) {
            'eq' => $contextValue == $value,
            'neq' => $contextValue != $value,
            'gt' => $contextValue > $value,
            'gte' => $contextValue >= $value,
            'lt' => $contextValue < $value,
            'lte' => $contextValue <= $value,
            'contains' => str_contains((string) $contextValue, (string) $value),
            default => false,
        };

        $branch = $result ? 'true' : 'false';

        return $block['branches'][$branch] ?? null;
    }

    protected function handleJumpBlock(array $block): ?string
    {
        return $block['config']['target_block_id'] ?? null;
    }

    protected function handleApiCallBlock(array $block, ConversationSession $session): ?string
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

        return $block['next_id'] ?? null;
    }

    protected function handleDelayBlock(array $block, ConversationSession $session): string
    {
        $seconds = $block['config']['seconds'] ?? 0;
        $nextId = $block['next_id'] ?? null;

        if ($nextId) {
            ProcessFlowStep::dispatch($session->id, $nextId)
                ->delay(now()->addSeconds($seconds))
                ->onQueue('telegram');
        } else {
            $session->update(['status' => ConversationSessionStatus::COMPLETED]);
        }

        return self::WAITING;
    }

    protected function handleInlineKeyboardBlock(array $block, ConversationSession $session): string
    {
        $text = $this->resolveText($block['content'] ?? [], $session);
        $buttons = $block['config']['buttons'] ?? [];

        $inlineKeyboard = [];
        foreach ($buttons as $btn) {
            $inlineKeyboard[] = [
                [
                    'text' => $btn['text'],
                    'callback_data' => $btn['value'] ?? $btn['text'],
                ],
            ];
        }

        SendTelegramMessage::dispatch(
            $this->bot,
            $this->subscriber->telegram_id,
            $text,
            inlineKeyboard: $inlineKeyboard,
        )->onQueue('telegram');

        // Ждём callback — не двигаемся дальше
        return self::WAITING;
    }

    protected function sendText(string $text): void
    {
        SendTelegramMessage::dispatch(
            $this->bot,
            $this->subscriber->telegram_id,
            $text,
        )->onQueue('telegram');
    }

    protected function resolveText(array $content, ConversationSession $session): string
    {
        $language = $this->subscriber->effectiveLanguage;
        $text = $content['translations'][$language]
            ?? $content['translations']['ru']
            ?? $content['text']
            ?? '';

        // Подстановка переменных. Раньше здесь заново запрашивалась
        // активная сессия отдельным запросом к БД на каждый текстовый
        // блок — теперь используем уже имеющуюся $session без лишнего запроса.
        $variables = $session->context;

        $variables['subscriber.telegram_id'] = $this->subscriber->telegram_id;
        $variables['subscriber.username'] = $this->subscriber->telegram_username ?? '';
        $variables['subscriber.language'] = $this->subscriber->effectiveLanguage;

        // Было $this->subscriber->people (такой связи не существует, в
        // модели BotSubscriber связь называется person()) — переменная
        // {{people.phone}} была всегда пустой. Исправлено на person.
        if ($this->subscriber->person) {
            $variables['people.phone'] = $this->subscriber->person->phone;
        }

        foreach ($variables as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace("{{{$key}}}", (string) $value, $text);
            }
        }

        // Убираем незаполненные плейсхолдеры
        return preg_replace('/\{\{[^}]+\}\}/', '', $text);
    }
}
