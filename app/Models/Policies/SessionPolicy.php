<?php

declare(strict_types=1);

namespace App\Models\Policies;

use App\Models\Admin\User\Session;
use App\Models\Admin\User\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('sessions.view');
    }

    public function view(User $user, Session $item): bool
    {
        return $user->can('sessions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sessions.create');
    }

    public function update(User $user, Session $item): bool
    {
        return $user->can('sessions.update');
    }

    public function delete(User $user, Session $item): bool
    {
        return $user->can('sessions.delete');
    }

    public function restore(User $user, Session $item): bool
    {
        return $user->can('sessions.update');
    }

    public function forceDelete(User $user, Session $item): bool
    {
        return $user->can('sessions.delete');
    }

    public function massDelete(User $user): bool
    {
        return $user->can('sessions.delete');
    }
}
