<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('permissions.view');
    }

    public function view(User $user, Permission $item): bool
    {
        return $user->can('permissions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('permissions.create');
    }

    public function update(User $user, Permission $item): bool
    {
        return $user->can('permissions.update');
    }

    public function delete(User $user, Permission $item): bool
    {
        return $user->can('permissions.delete');
    }

    public function restore(User $user, Permission $item): bool
    {
        return $user->can('permissions.update');
    }

    public function forceDelete(User $user, Permission $item): bool
    {
        return $user->can('permissions.delete');
    }

    public function massDelete(User $user): bool
    {
        return $user->can('permissions.delete');
    }
}
