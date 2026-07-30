<?php

namespace App\MoonShine\Resources\Users\Permission\Pages;

use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Permission\Pages\FieldContract;
use App\MoonShine\Resources\Users\Role\RoleResource;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Text;

/**
 * Класс PermissionFormPage представляет страницу формы для работы с разрешениями пользователей.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
class PermissionFormPage extends BaseFormPage
{
    /**
     * @return FieldContract
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
