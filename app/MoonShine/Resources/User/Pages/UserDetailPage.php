<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\User\Pages;

use App\MoonShine\Resources\Permission\PermissionResource;
use App\MoonShine\Resources\Role\RoleResource;
use App\MoonShine\Resources\Session\SessionResource;
use App\Services\DeviceDetector;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends DetailPage<UserResource>
 */
class UserDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Image::make(__('moonshine::ui.resource.avatar'), 'avatar')->modifyRawValue(fn (
                ?string $raw
            ): string => $raw ?? ''),
            Text::make(__('moonshine::ui.resource.name'), 'name'),
            Email::make(__('moonshine::ui.resource.email'), 'email'),
            BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')->inLine(separator: ', '),
            BelongsToMany::make('Разрешения', 'permissions', resource: PermissionResource::class, formatted: 'name')->inLine(separator: ', '),
            Switcher::make('Активный', 'is_active'),
            Switcher::make('Включен: 2FA', 'enabled_2fa'),
            Switcher::make('Включен: Несколько устройств', 'enabled_multi_device_login'),
            Date::make('Дата обновления пароля', 'password_changed_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
            HasMany::make( 'Сессии', 'sessions', resource: SessionResource::class)
                ->fields([
                    ID::make(),
                    Text::make('IP', 'ip_address'),
                    Text::make('Устройство', 'user_agent')
                        ->changePreview(fn (?string $value, Text $field) => DeviceDetector::detect($value)),
                    Text::make('User Agent', 'user_agent'),
                    Date::make('Последняя активность', 'last_activity')
                        ->format('d.m.Y H:i:s'),
                ])
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyDetailComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
