<?php

namespace App\Domain\Users;

use App\Domain\Users\Traits\LogsUserActivity;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Модель для работы с ролями пользователей:
 * наследуется от SpatieRole и использует трейт LogsUserActivity для логирования действий пользователей.
 */
class Role extends SpatieRole
{
    use LogsUserActivity, LogsUserActivity;

    protected static function logLabel(): string
    {
        return 'Роль';
    }

    protected $with = ['permissions'];
}
