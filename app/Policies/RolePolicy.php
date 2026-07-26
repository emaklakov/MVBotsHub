<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $item): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $item): bool
    {
        return $user->can('roles.update');
    }

    public function delete(User $user, Role $item): bool
    {
        return $user->can('roles.delete');
    }

    public function restore(User $user, Role $item): bool
    {
        return $user->can('roles.update');
    }

    public function forceDelete(User $user, Role $item): bool
    {
        return $user->can('roles.delete');
    }

    public function massDelete(User $user): bool
    {
        return $user->can('roles.delete');
    }
}
