<?php

declare(strict_types=1);

namespace App\Domain\Bots\Contracts;

/**
 * Реализуется джобами очереди 'telegram', которые делают реальный HTTP-запрос
 * к Bot API. Telegram лимитирует запросы ПО ТОКЕНУ БОТА (~30 msg/sec),
 * а не по IP сервера — поэтому лимитер должен быть keyed по botId(),
 * а не общим на всё приложение. См. RateLimiter::for('telegram', ...)
 * в AppServiceProvider::boot().
 */
interface HasBotRateLimitKey
{
    public function botId(): int;
}
