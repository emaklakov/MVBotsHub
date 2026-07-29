<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Role\Pages;

use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Permission\PermissionResource;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\Role\RoleResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends FormPage<RoleResource>
 */
class RoleFormPage extends BaseFormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
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
