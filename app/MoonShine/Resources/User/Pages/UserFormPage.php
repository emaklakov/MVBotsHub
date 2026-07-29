<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\User\Pages;

use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Permission\PermissionResource;
use App\MoonShine\Resources\Role\RoleResource;
use Illuminate\Validation\Rules\Password;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Collapse;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\PasswordRepeat;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<UserResource>
 */
class UserFormPage extends BaseFormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Grid::make([
                    Column::make(
                        [
                            Switcher::make('Активный', 'is_active'),
                        ],
                        colSpan: 4,
                        adaptiveColSpan: 12
                    ),
                    Column::make(
                        [
                            Switcher::make('2FA', 'enabled_2fa'),
                        ],
                        colSpan: 4,
                        adaptiveColSpan: 6
                    ),
                    Column::make(
                        [
                            Switcher::make('Несколько устройств', 'enabled_multi_device_login'),
                        ],
                        colSpan: 4,
                        adaptiveColSpan: 6
                    ),
                ]),
                Image::make(__('moonshine::ui.resource.avatar'), 'avatar')
                    ->disk(moonshineConfig()->getDisk())
                    ->dir(moonshineConfig()->getUserAvatarsDir())
                    ->allowedExtensions(['jpg', 'png', 'jpeg', 'gif']),
                Text::make(__('moonshine::ui.resource.name'), 'name'),
                Email::make(__('moonshine::ui.resource.email'), 'email')->disabled(),
                Divider::make('Доступы')->centered(),
                BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')->inLine(separator: ', ')->selectMode(),
                BelongsToMany::make('Разрешения', 'permissions', resource: PermissionResource::class, formatted: 'name')->selectMode(),
                Divider::make('Пароль')->centered(),
                Collapse::make(__('moonshine::ui.resource.change_password'), [
                    \MoonShine\UI\Fields\Password::make(__('moonshine::ui.resource.password'), 'password')
                        ->customAttributes(['autocomplete' => 'new-password'])
                        ->eye(),

                    PasswordRepeat::make(__('moonshine::ui.resource.repeat_password'), 'password_confirmation')
                        ->customAttributes(['autocomplete' => 'confirm-password'])
                        ->eye(),
                ])->icon('lock-closed'),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'password' => $item->exists
                ? ['nullable', 'confirmed', Password::defaults()]
                : ['required', 'confirmed', Password::defaults()],
        ];
    }
}
