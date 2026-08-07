<?php

declare(strict_types=1);

namespace App\Application\Bots\Services;

use App\Domain\Bots\Enums\SystemMessageKey;
use App\Domain\Bots\Models\Bot;
use App\Domain\Bots\Models\BotMessageTemplate;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Flows\Contracts\VariableResolverInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Единая точка получения текста системных сообщений бота (не из FlowVersion).
 *
 * Цепочка fallback:
 *   1. Перевод в BotMessageTemplate на языке подписчика (subscriber->effectiveLanguage)
 *   2. Перевод в BotMessageTemplate на языке бота по умолчанию (bot->settings['language'])
 *   3. Встроенный дефолт SystemMessageKey::fallback() на языке подписчика
 *   4. Встроенный дефолт SystemMessageKey::fallback() на русском — последний рубеж,
 *      бот никогда не должен отправить пустую строку.
 */
final class SystemMessageResolver
{
    private const int CACHE_TTL = 3600;

    public function __construct(
        private readonly VariableResolverInterface $variableResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $variables
     */
    public function resolve(
        Bot $bot,
        SystemMessageKey $key,
        BotSubscriber $subscriber,
        array $variables = [],
    ): string {
        $language = $subscriber->effectiveLanguage;
        $botDefaultLanguage = $bot->settings['language'] ?? null;
        $translations = $this->translationsFor($bot, $key);
        $fallback = $key->fallback();

        $raw = $translations[$language]
            ?? $translations[$botDefaultLanguage]
            ?? $fallback[$language]
            ?? $fallback['ru'];

        return $this->variableResolver->resolve($raw, $variables, $subscriber);
    }

    /**
     * @return array<string, string>
     */
    private function translationsFor(Bot $bot, SystemMessageKey $key): array
    {
        return Cache::remember(
            self::cacheKey($bot->id, $key),
            self::CACHE_TTL,
            fn () => BotMessageTemplate::query()
                ->where('bot_id', $bot->id)
                ->where('key', $key->value)
                ->value('translations') ?? [],
        );
    }

    public static function forgetCache(int $botId, SystemMessageKey $key): void
    {
        Cache::forget(self::cacheKey($botId, $key));
    }

    private static function cacheKey(int $botId, SystemMessageKey $key): string
    {
        return "bot-message-template:{$botId}:{$key->value}";
    }
}
