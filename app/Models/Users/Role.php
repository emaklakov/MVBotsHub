<?php

namespace App\Models\Users;

use App\Models\Users\Traits\LogsUserActivity;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Модель для работы с ролями пользователей:
 * наследуется от SpatieRole и использует трейт LogsUserActivity для логирования действий пользователей.
 */
class Role extends SpatieRole
{
    protected $with = ['permissions'];

    use LogsUserActivity;
}
