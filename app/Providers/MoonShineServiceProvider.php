<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Resources\Jobs\FailedJob\FailedJobResource;
use App\MoonShine\Resources\Jobs\Job\JobResource;
use App\MoonShine\Resources\Jobs\JobLog\JobLogResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Bots\BotMember\BotMemberResource;
use App\MoonShine\Resources\Telegram\BotSubscriber\BotSubscriberResource;
use App\MoonShine\Resources\Users\Notification\NotificationResource;
use App\MoonShine\Resources\Users\Permission\PermissionResource;
use App\MoonShine\Resources\Users\Role\RoleResource;
use App\MoonShine\Resources\Users\Session\SessionResource;
use App\MoonShine\Resources\Users\User\UserResource;
use App\MoonShine\Resources\Users\UserLog\UserLogResource;
use App\MoonShine\Resources\Users\UserSetting\UserSettingResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

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
                NotificationResource::class,
                BotResource::class,
                BotSubscriberResource::class,
                BotMemberResource::class,
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
            ])
        ;
    }
}
