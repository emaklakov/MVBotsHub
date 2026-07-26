<?php

namespace App\Models;

use App\Models\Concerns\LogsUserActivity;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $with = ['permissions'];

    use LogsUserActivity;
}
