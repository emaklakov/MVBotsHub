<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\User;

use App\Domain\Users\User;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Users\Permission\PermissionResource;
use App\MoonShine\Resources\Users\Role\RoleResource;
use App\MoonShine\Resources\Users\User\Pages\UserDetailPage;
use App\MoonShine\Resources\Users\User\Pages\UserFormPage;
use App\MoonShine\Resources\Users\User\Pages\UserIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * Класс UserResource представляет ресурс для работы с пользователями.
 * Определяет заголовок, компоненты и другие параметры ресурса.
 */
class UserResource extends BaseResource
{
    protected string $model = User::class;

    protected string $title = 'Пользователи';

    protected array $with = ['roles', 'permissions'];

    // Отключаем массовое удаление
    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::MASS_DELETE);
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            UserIndexPage::class,
            UserFormPage::class,
            UserDetailPage::class,
        ];
    }

    protected function exportFields(): iterable
    {
        return [
            ID::make(),

            Switcher::make('Активный', 'is_active')
                ->modifyRawValue(static fn (mixed $raw, User $data, Switcher $ctx) => $raw ? 'Да' : 'Нет'),

            Text::make(__('moonshine::ui.resource.name'), 'name'),

            Email::make(__('moonshine::ui.resource.email'), 'email'),

            BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')
                ->modifyRawValue(static fn (mixed $raw, User $data, BelongsToMany $ctx) => $data->roles
                    ->pluck('name')
                    ->implode(', ')),

            BelongsToMany::make('Разрешения', 'permissions', resource: PermissionResource::class, formatted: 'name')
                ->modifyRawValue(static fn (mixed $raw, User $data, BelongsToMany $ctx) => $data->permissions
                    ->pluck('name')
                    ->implode(', ')),

            Switcher::make('Включен: 2FA', 'enabled_2fa')
                ->modifyRawValue(static fn (mixed $raw, User $data, Switcher $ctx) => $raw ? 'Да' : 'Нет'),

            Switcher::make('Включен: Несколько устройств', 'enabled_multi_device_login')
                ->modifyRawValue(static fn (mixed $raw, User $data, Switcher $ctx) => $raw ? 'Да' : 'Нет'),

            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->modifyRawValue(static fn (mixed $raw, User $data, Date $ctx) => $data->created_at?->format('d.m.Y H:i:s') ?? ''),

            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->modifyRawValue(static fn (mixed $raw, User $data, Date $ctx) => $data->updated_at?->format('d.m.Y H:i:s') ?? ''),
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'name',
            'email',
        ];
    }
}
