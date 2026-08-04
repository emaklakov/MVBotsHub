<?php

namespace App\Policies;

use App\Domain\Bots\Models\Bot;
use App\Domain\Flows\Models\Flow;
use App\Models\Users\User;

class FlowPolicy
{
    public function view(User $user, Flow $flow): bool
    {
        return $this->hasBotAccess($user, $flow->bot);
    }

    public function create(User $user, Flow $flow): bool
    {
        return $this->hasBotAccess($user, $flow->bot);
    }

    public function update(User $user, Flow $flow): bool
    {
        return $this->hasBotAccess($user, $flow->bot);
    }

    public function delete(User $user, Flow $flow): bool
    {
        return $this->hasBotAccess($user, $flow->bot);
    }

    private function hasBotAccess(User $user, Bot $bot): bool
    {
        return $bot->members()->where('user_id', $user->id)->exists();
    }
}
