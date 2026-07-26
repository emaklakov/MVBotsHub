<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\User\Pages;

use App\MoonShine\Resources\Permission\PermissionResource;
use App\MoonShine\Resources\Role\RoleResource;
use Illuminate\Validation\Rules\Password;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\Collapse;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\PasswordRepeat;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends FormPage<UserResource>
 */
class UserFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Switcher::make('Активный', 'is_active'),
                Image::make(__('moonshine::ui.resource.avatar'), 'avatar')
                    ->disk(moonshineConfig()->getDisk())
                    ->dir(moonshineConfig()->getUserAvatarsDir())
                    ->allowedExtensions(['jpg', 'png', 'jpeg', 'gif']),
                Text::make(__('moonshine::ui.resource.name'), 'name'),
                Email::make(__('moonshine::ui.resource.email'), 'email')->disabled(),
                Switcher::make('Включен: 2FA', 'enabled_2fa'),
                Switcher::make('Включен: Несколько устройств', 'enabled_multi_device_login'),
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

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons();
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'password' => $item->exists
                ? ['nullable', 'confirmed', Password::defaults()]
                : ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @param  FormBuilder  $component
     *
     * @return FormBuilder
     */
    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
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
