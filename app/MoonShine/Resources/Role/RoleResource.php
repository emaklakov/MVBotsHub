<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Role;

use Illuminate\Database\Eloquent\Model;
use App\Models\Role;
use App\MoonShine\Resources\Role\Pages\RoleIndexPage;
use App\MoonShine\Resources\Role\Pages\RoleFormPage;
use App\MoonShine\Resources\Role\Pages\RoleDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Role, RoleIndexPage, RoleFormPage>
 */
class RoleResource extends ModelResource
{
    protected bool $withPolicy = true;

    protected string $model = Role::class;

    protected string $title = 'Роли';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            RoleIndexPage::class,
            RoleFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'name',
        ];
    }
}
