<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Redis;

class TelegramBotThrottle
{
    public function __construct(private readonly int $botId) {}

    public function handle($job, $next)
    {
        Redis::throttle("telegram-bot:{$this->botId}")
            ->allow(25)->every(1)
            ->then(
                fn () => $next($job),
                fn () => $job->release(1), // не удалось получить слот — вернуть в очередь через 1 сек
            );
    }
}
