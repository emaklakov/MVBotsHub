<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Admin\UserLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;

class UserLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('users-logs.view');
    }

    public function view(User $user, UserLog $item): bool
    {
        return $user->can('users-logs.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users-logs.create');
    }

    public function update(User $user, UserLog $item): bool
    {
        return $user->can('users-logs.update');
    }

    public function delete(User $user, UserLog $item): bool
    {
        return $user->can('users-logs.delete');
    }

    public function restore(User $user, UserLog $item): bool
    {
        return $user->can('users-logs.update');
    }

    public function forceDelete(User $user, UserLog $item): bool
    {
        return $user->can('users-logs.delete');
    }

    public function massDelete(User $user): bool
    {
        return $user->can('users-logs.delete');
    }
}
