<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Pages\Admin\Flows\FlowEditor;
use App\MoonShine\Resources\CRM\Person\PersonResource;
use App\MoonShine\Resources\Jobs\FailedJob\FailedJobResource;
use App\MoonShine\Resources\Jobs\Job\JobResource;
use App\MoonShine\Resources\Jobs\JobLog\JobLogResource;
use App\MoonShine\Resources\Telegram\Audiences\Audience\AudienceResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Bots\BotMember\BotMemberResource;
use App\MoonShine\Resources\Telegram\Bots\BotMessageTemplate\BotMessageTemplateResource;
use App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\BroadcastResource;
use App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient\BroadcastRecipientResource;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\BotSubscriberResource;
use App\MoonShine\Resources\Telegram\Conversations\Conversation\ConversationResource;
use App\MoonShine\Resources\Telegram\Conversations\ConversationSession\ConversationSessionResource;
use App\MoonShine\Resources\Telegram\Conversations\Message\MessageResource;
use App\MoonShine\Resources\Telegram\Flows\Flow\FlowResource;
use App\MoonShine\Resources\Telegram\Flows\FlowVersion\FlowVersionResource;
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
                BotMessageTemplateResource::class,
                PersonResource::class,
                ConversationResource::class,
                FlowResource::class,
                FlowVersionResource::class,
                ConversationSessionResource::class,
                MessageResource::class,
                BroadcastResource::class,
                BroadcastRecipientResource::class,
                AudienceResource::class,
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
                FlowEditor::class,
            ])
        ;
    }
}
