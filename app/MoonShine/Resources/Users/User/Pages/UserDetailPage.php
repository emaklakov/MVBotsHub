<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\User\Pages;

use App\Application\Users\Services\DeviceDetector;
use App\Domain\Users\UserSetting;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Users\Permission\PermissionResource;
use App\MoonShine\Resources\Users\Role\RoleResource;
use App\MoonShine\Resources\Users\Session\SessionResource;
use App\MoonShine\Resources\Users\User\UserResource;
use App\MoonShine\Resources\Users\UserLog\UserLogResource;
use App\MoonShine\Resources\Users\UserSetting\UserSettingResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;


/**
 * @extends DetailPage<UserResource>
 */
class UserDetailPage extends BaseDetailPage
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
                ])->tabMode(),
            HasMany::make( 'Настройки', 'settings', resource: UserSettingResource::class)
                ->fields([
                    ID::make(),
                    Text::make('Название', 'name'),
                    Text::make('Ключ', 'key'),
                    Checkbox::make('Зашифровано', 'encrypted'),
                    Text::make('Значение', 'value', function (UserSetting $item) {
                        return $item->encrypted ? '••••••••' : $item->value;
                    }),
                ])->tabMode()->creatable(),
            HasMany::make( 'Логи', 'logs', resource: UserLogResource::class)
                ->fields([
                    ID::make()->sortable(),
                    Date::make('Дата', 'created_at')->format('d.m.Y H:i:s')->sortable(),
                    Text::make('Действие', 'action')->sortable(),
                    Text::make('Объект', 'subject_type')
                        ->changePreview(fn ($value, $field) => $value
                            ? class_basename($value) . ' #' . $field->getData()->subject_id
                            : '—'),
                    Text::make('IP', 'ip_address'),
                ])->tabMode(),
        ];
    }
}
