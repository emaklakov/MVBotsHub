<?php

namespace App\Policies;

use App\Domain\Bots\Enums\BotMemberRole;
use App\Domain\Bots\Models\Bot;
use App\Domain\Users\User;

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
        $role = $bot->members()->where('user_id', $user->id)->value('role');
        return in_array($role, [BotMemberRole::ADMIN, BotMemberRole::OWNER]);
    }

    public function delete(User $user, Bot $bot): bool
    {
        $role = $bot->members()->where('user_id', $user->id)->value('role');
        return in_array($role, [BotMemberRole::ADMIN, BotMemberRole::OWNER]);
    }

    public function registerWebhook(User $user, Bot $bot): bool
    {
        return $this->update($user, $bot);
    }

    private function hasAccess(User $user, Bot $bot): bool
    {
        return $bot->members()->where('user_id', $user->id)->exists();
    }
}
