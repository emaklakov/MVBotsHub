<?php

declare(strict_types=1);

namespace App\Models\Policies;

use App\Models\Admin\User\User;
use App\Models\Admin\User\UserSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('users-settings.view');
    }

    public function view(User $user, UserSetting $item): bool
    {
        return $user->can('users-settings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users-settings.create');
    }

    public function update(User $user, UserSetting $item): bool
    {
        return $user->can('users-settings.update');
    }

    public function delete(User $user, UserSetting $item): bool
    {
        return $user->can('users-settings.delete');
    }

    public function restore(User $user, UserSetting $item): bool
    {
        return $user->can('users-settings.update');
    }

    public function forceDelete(User $user, UserSetting $item): bool
    {
        return $user->can('users-settings.delete');
    }

    public function massDelete(User $user): bool
    {
        return $user->can('users-settings.delete');
    }
}
