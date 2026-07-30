<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\Role\Pages;

use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Users\Permission\PermissionResource;
use App\MoonShine\Resources\Users\Role\RoleResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Text;


/**
 * @extends FormPage<RoleResource>
 */
class RoleFormPage extends BaseFormPage
{
    /**
     * @return FieldContract
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                Text::make('Название', 'name')->required(),
                BelongsToMany::make('Права', 'permissions', resource: PermissionResource::class, formatted: 'name')
                    ->selectMode(),
            ]),
        ];
    }
}
