<?php

namespace App\Policies;

use App\Domain\Bots\Models\Bot;
use App\Models\Users\User;

class BotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bots.view');
    }

    public function view(User $user, Bot $bot): bool
    {
        return $this->hasAccess($user, $bot);
    }

    public function create(User $user): bool
    {
        return $user->can('bots.create');
    }

    public function update(User $user, Bot $bot): bool
    {
        if ($bot->owner_id === $user->id) {
            return true;
        }
        $role = $bot->users()->where('user_id', $user->id)->value('role');
        return in_array($role, ['admin', 'owner']);
    }

    public function delete(User $user, Bot $bot): bool
    {
        return $bot->owner_id === $user->id;
    }

    public function registerWebhook(User $user, Bot $bot): bool
    {
        return $this->update($user, $bot);
    }

    private function hasAccess(User $user, Bot $bot): bool
    {
        if ($bot->owner_id === $user->id) {
            return true;
        }
        return $bot->users()->where('user_id', $user->id)->exists();
    }
}
