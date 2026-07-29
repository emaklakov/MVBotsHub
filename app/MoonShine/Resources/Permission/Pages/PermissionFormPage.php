<?php

namespace App\MoonShine\Resources\Permission\Pages;

use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Role\RoleResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Text;

/**
 * Класс PermissionFormPage представляет страницу формы для работы с разрешениями пользователей.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
class PermissionFormPage extends BaseFormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                Text::make('Название', 'name')->required(),
                BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')->selectMode(),
            ]),
        ];
    }
}
