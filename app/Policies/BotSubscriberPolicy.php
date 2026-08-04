<?php

namespace App\Policies;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Models\Users\User;

class BotSubscriberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bot-subscribers.view');
    }

    public function view(User $user, BotSubscriber $subscriber): bool
    {
        return $this->hasBotAccess($user, $subscriber->bot_id);
    }

    private function hasBotAccess(User $user, int $botId): bool
    {
        $bot = Bot::find($botId);
        if (!$bot) {
            return false;
        }
        return $bot->members()->where('user_id', $user->id)->exists();
    }
}
