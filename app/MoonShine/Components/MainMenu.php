<?php

namespace App\MoonShine\Components;

use App\Domain\Bots\Models\Bot;
use App\Domain\Bots\Models\BotMember;
use App\Domain\Broadcasts\Models\Broadcast;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Conversations\Models\ConversationSession;
use App\Domain\Conversations\Models\Message;
use App\Domain\CRM\Models\Person;
use App\Domain\Flows\Models\Flow;
use App\Domain\Flows\Models\FlowVersion;
use App\Domain\Queue\FailedJob;
use App\Domain\Queue\Job;
use App\Domain\Queue\JobLog;
use App\Domain\Users\Notification;
use App\Domain\Users\Role;
use App\Domain\Users\Session;
use App\Domain\Users\User;
use App\Domain\Users\UserLog;
use App\Domain\Users\UserSetting;
use App\MoonShine\Resources\CRM\Person\PersonResource;
use App\MoonShine\Resources\Jobs\FailedJob\FailedJobResource;
use App\MoonShine\Resources\Jobs\Job\JobResource;
use App\MoonShine\Resources\Jobs\JobLog\JobLogResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Bots\BotMember\BotMemberResource;
use App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\BroadcastResource;
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
use Illuminate\Support\Facades\Gate;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use Spatie\Permission\Models\Permission;

class MainMenu
{
    public static function menu(): array
    {
        return [
            MenuGroup::make('Пользователи', [
                MenuItem::make(UserResource::class,'Пользователи', 'users')
                    ->canSee(fn () => Gate::allows('viewAny', User::class)),
                MenuItem::make(RoleResource::class,'Роли', 'shield-exclamation')
                    ->canSee(fn () => Gate::allows('viewAny', Role::class)),
                MenuItem::make(PermissionResource::class,'Разрешения', 'shield-check')
                    ->canSee(fn () => Gate::allows('viewAny', Permission::class)),
                MenuItem::make(SessionResource::class,'Сессии', 'arrow-right-end-on-rectangle')
                    ->canSee(fn () => Gate::allows('viewAny', Session::class)),
                MenuItem::make(UserLogResource::class, 'Логи действий')
                    ->icon('shoe-prints', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', UserLog::class)),
                MenuItem::make(UserSettingResource::class, 'Настройки пользователей', 'cog-8-tooth')
                    ->canSee(fn () => Gate::allows('viewAny', UserSetting::class)),
                MenuItem::make(NotificationResource::class, 'Уведомления', 'bell-alert')
                    ->canSee(fn () => Gate::allows('viewAny', Notification::class)),
            ], 'user-group'),
            MenuGroup::make('Система', [

            ], 'cpu-chip'),
            MenuGroup::make('Очереди', [
                MenuItem::make(JobResource::class, 'Журнал очередей DB', 'square-3-stack-3d')
                    ->canSee(fn () => Gate::allows('viewAny', Job::class)),
                MenuItem::make(url('/horizon'), 'Horizon (Очереди Redis)')
                    ->blank()->icon('grip', path: 'icons')
                    ->canSee(fn () => \Auth::user()->hasRole('admin') || \Auth::user()->hasRole('super-admin')),
                MenuItem::make(FailedJobResource::class, 'Задачи с ошибками', 'exclamation-triangle')
                    ->canSee(fn () => Gate::allows('viewAny', FailedJob::class)),
                MenuItem::make(JobLogResource::class, 'Логи очередей', 'rectangle-stack')
                    ->canSee(fn () => Gate::allows('viewAny', JobLog::class)),
            ], 'square-3-stack-3d'),
            MenuGroup::make('CRM', [
                MenuItem::make(PersonResource::class, 'Люди')
                    ->icon('people-group', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', Person::class)),
            ])->icon('window-restore', path: 'icons'),
            MenuGroup::make('Telegram', [
                MenuItem::make(BotResource::class, 'Боты')
                    ->icon('robot', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', Bot::class)),
                MenuItem::make(FlowResource::class, 'Потоки')
                    ->icon('chart-diagram', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', Flow::class)),
                MenuItem::make(FlowVersionResource::class, 'Версии потоков')
                    ->icon('diagram-project', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', FlowVersion::class)),
                MenuItem::make(BotMemberResource::class, 'Доступы к ботам')
                    ->icon('eye-low-vision', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', BotMember::class)),
                MenuItem::make(BotSubscriberResource::class, 'Пользователи бота', 'user-group')
                    ->canSee(fn () => Gate::allows('viewAny', BotSubscriber::class)),
                MenuItem::make(ConversationResource::class, 'Диалоги')
                    ->icon('people-arrows', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', Conversation::class)),
                MenuItem::make(ConversationSessionResource::class, 'Сессии диалогов')
                    ->icon('hourglass-half', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', ConversationSession::class)),
                MenuItem::make(MessageResource::class, 'Сообщения')
                    ->icon('comments-regular', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', Message::class)),
                MenuItem::make(BroadcastResource::class, 'Рассылки')
                    ->icon('tower-cell', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', Broadcast::class)),
            ])->icon('telegram', path: 'icons'),
        ];
    }
}
