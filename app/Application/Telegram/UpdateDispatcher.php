<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use App\Infrastructure\Telegram\DTO\TelegramUpdate;
use Illuminate\Support\Facades\Log;

final readonly class UpdateDispatcher
{
    public function __construct(
        private MessageHandler       $messageHandler,
        private CallbackQueryHandler $callbackQueryHandler,
    ) {}

    public function dispatch(Bot $bot, TelegramUpdate $update): void
    {
        match (true) {
            $update->message() !== null => $this->messageHandler->handle($bot, $update->message()),
            $update->callbackQuery() !== null => $this->callbackQueryHandler->handle($bot, $update->callbackQuery()),
            default                          => $this->logUnsupported($update),
        };
    }

    private function logUnsupported(TelegramUpdate $update): void
    {
        LogService::logInfo('Запрошен Unsupported Update', $update);
    }
}
