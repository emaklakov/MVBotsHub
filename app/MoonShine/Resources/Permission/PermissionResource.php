<?php

namespace App\MoonShine\Resources\Permission;

use App\MoonShine\Resources\Permission\Pages\PermissionFormPage;
use App\MoonShine\Resources\Permission\Pages\PermissionIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use Spatie\Permission\Models\Permission;

class PermissionResource extends ModelResource
{
    protected bool $withPolicy = true;

    protected string $model = Permission::class;

    protected string $title = 'Разрешения';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::MASS_DELETE);
    }

    protected function pages(): array
    {
        return [
            PermissionIndexPage::class,
            PermissionFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'name',
        ];
    }
}
