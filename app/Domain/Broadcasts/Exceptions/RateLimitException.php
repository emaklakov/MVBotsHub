<?php

declare(strict_types=1);

namespace App\Domain\Broadcasts\Exceptions;

/**
 * Сигнализирует Job'у, что нужно отложить выполнение на N секунд.
 * Не является ошибкой — это ожидаемое поведение при 429 от Telegram.
 */
final class RateLimitException extends \RuntimeException
{
    public function __construct(
        public readonly int $retryAfter,
        string $message = 'В Telegram достигнут лимит запросов.',
    ) {
        parent::__construct($message);
    }
}
