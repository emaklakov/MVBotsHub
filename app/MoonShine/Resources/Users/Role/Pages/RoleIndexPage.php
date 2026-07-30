<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\Role\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Users\Permission\PermissionResource;
use App\MoonShine\Resources\Users\Role\RoleResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<RoleResource>
 */
class RoleIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name'),
            BelongsToMany::make('Разрешения', 'permissions', resource: PermissionResource::class, formatted: 'name')->inLine(separator: ', '),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Text::make('Название', 'name'),
            BelongsToMany::make('Разрешения', 'permissions', resource: PermissionResource::class, formatted: 'name')
            ->selectMode()
            ->nullable()
        ];
    }
}
