<?php

namespace App\MoonShine\Resources\Users\Permission\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Users\Role\RoleResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * Класс PermissionIndexPage представляет страницу индекса для работы с разрешениями пользователей.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
class PermissionIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name')->sortable(),
            BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')->inLine(separator: ', '),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Text::make('Название', 'name'),
            BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')
                ->selectMode()
                ->nullable()
        ];
    }
}
