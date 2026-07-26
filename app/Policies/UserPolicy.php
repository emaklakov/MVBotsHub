<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $item): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $item): bool
    {
        return $user->can('users.update');
    }

    public function delete(User $user, User $item): bool
    {
        return $user->can('users.delete');
    }

    public function restore(User $user, User $item): bool
    {
        return $user->can('users.update');
    }

    public function forceDelete(User $user, User $item): bool
    {
        return $user->can('users.delete');
    }

    public function massDelete(User $user): bool
    {
        return $user->can('users.delete');
    }
}
