<?php

declare(strict_types=1);

namespace App\Domain\Bots\Observers;

use App\Domain\Bots\Enums\SystemMessageKey;
use App\Domain\Bots\Models\Bot;
use App\Domain\Bots\Models\BotMessageTemplate;

/**
 * При создании нового бота сразу заводим строки BotMessageTemplate для всех
 * известных SystemMessageKey с их встроенными переводами по умолчанию.
 * Это не обязательно (SystemMessageResolver и так упадёт на fallback()
 * из enum, если строки нет), но так админ сразу видит в MoonShine полный
 * список редактируемых сообщений, а не пустую таблицу.
 */
final class BotObserver
{
    public function created(Bot $bot): void
    {
        $rows = collect(SystemMessageKey::cases())->map(fn (SystemMessageKey $key) => [
            'bot_id' => $bot->id,
            'key' => $key->value,
            'translations' => json_encode($key->fallback()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        BotMessageTemplate::query()->insert($rows->toArray());
    }
}
