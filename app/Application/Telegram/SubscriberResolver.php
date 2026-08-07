<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\BotSubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Infrastructure\Telegram\DTO\User as TelegramUser;
use Illuminate\Support\Facades\Log;

final class SubscriberResolver
{
    public function resolve(Bot $bot, TelegramUser $user): BotSubscriber
    {
        $subscriber = BotSubscriber::updateOrCreate(
            ['bot_id' => $bot->id, 'telegram_id' => $user->id()],
            [
                'telegram_username'   => $user->username(),
                'telegram_first_name' => $user->firstName(),
                'telegram_last_name'  => $user->lastName(),
                'telegram_language'   => $user->languageCode(),
                'is_bot'              => $user->isBot(),
                //'status'              => BotSubscriberStatus::ACTIVE,
                'settings'            => [],
                'language'            => $bot->settings['language'] ?? config('app.locale'),
            ]
        );

        $subscriber->timestamps = false;

        if($subscriber->status !== BotSubscriberStatus::DISABLED && $subscriber->status !== BotSubscriberStatus::MERGED) {
            $subscriber->status = BotSubscriberStatus::ACTIVE;
        }

        $subscriber->last_activity_at = now();
        $subscriber->save();
        $subscriber->timestamps = true;

        return $subscriber;
    }
}
