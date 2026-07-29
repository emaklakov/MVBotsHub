<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\User\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Permission\PermissionResource;
use App\MoonShine\Resources\Role\RoleResource;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<UserResource>
 */
class UserIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Switcher::make('Активный', 'is_active')->sortable(),
            Text::make(__('moonshine::ui.resource.name'), 'name'),
            Email::make(__('moonshine::ui.resource.email'), 'email')
                ->sortable(),
            BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')->inLine(separator: ', '),
            BelongsToMany::make('Разрешения', 'permissions', resource: PermissionResource::class, formatted: 'name')->inLine(separator: ', '),
            Switcher::make('Включен: 2FA', 'enabled_2fa')->sortable(),
            Switcher::make('Включен: Несколько устройств', 'enabled_multi_device_login')->sortable(),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s')
                ->sortable(),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s')
                ->sortable(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Checkbox::make('Активный', 'is_active'),
            Number::make('ID', 'id'),
            Text::make(__('moonshine::ui.resource.name'), 'name'),
            Email::make(__('moonshine::ui.resource.email'), 'email'),
            BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')
                ->selectMode()
                ->nullable(),
            BelongsToMany::make('Разрешения', 'permissions', resource: PermissionResource::class, formatted: 'name')
                ->selectMode()
                ->nullable(),
            Checkbox::make('Включен: 2FA', 'enabled_2fa'),
            Checkbox::make('Включен: Несколько устройств', 'enabled_multi_device_login'),
        ];
    }
}
