<?php

namespace App\Infrastructure\Telegram\DTO;

use DefStudio\Telegraph\DTO\TelegramUpdate as TelegramUpdateDTO;

final  class TelegramUpdate extends TelegramUpdateDTO
{
    public function __construct(
        public int $updateId,
        public ?TelegramMessage $message,
        public ?TelegramCallbackQuery $callbackQuery,
    ) {}
}
