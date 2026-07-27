<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Resources\Permission\PermissionResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;
use App\MoonShine\Resources\Role\RoleResource;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\Session\SessionResource;
use App\MoonShine\Resources\UserLog\UserLogResource;
use App\MoonShine\Resources\UserSetting\UserSettingResource;
use App\MoonShine\Resources\JobLog\JobLogResource;
use App\MoonShine\Resources\Job\JobResource;
use App\MoonShine\Resources\FailedJob\FailedJobResource;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([
                RoleResource::class,
                PermissionResource::class,
                UserResource::class,
                SessionResource::class,
                UserLogResource::class,
                UserSettingResource::class,
                JobLogResource::class,
                JobResource::class,
                FailedJobResource::class,
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
            ])
        ;
    }
}
