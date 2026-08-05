<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\SubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;

final class SubscriberResolver
{
    public function resolve(Bot $bot, int $telegramId, ?string $username): BotSubscriber
    {
        return BotSubscriber::firstOrCreate(
            ['bot_id' => $bot->id, 'telegram_id' => $telegramId],
            [
                'telegram_username' => $username,
                'status'            => SubscriberStatus::ACTIVE,
                'settings'          => [],
                'language'          => $bot->settings['language'] ?? config('app.locale'),
            ]
        );
    }
}
