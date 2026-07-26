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
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
            ])
        ;
    }
}
