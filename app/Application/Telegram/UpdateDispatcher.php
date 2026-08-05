<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;

final class UpdateDispatcher
{
    public function __construct(
        private readonly MessageHandler $messageHandler,
        private readonly CallbackQueryHandler $callbackQueryHandler,
    ) {}

    public function dispatch(Bot $bot, array $update): void
    {
        match (true) {
            isset($update['message'])        => $this->messageHandler->handle($bot, $update['message']),
            isset($update['callback_query']) => $this->callbackQueryHandler->handle($bot, $update['callback_query']),
            default                          => $this->logUnsupported($update),
        };
    }

    private function logUnsupported(array $update): void
    {
        LogService::logInfo('Запрошен Unsupported Update', $update);
    }
}
