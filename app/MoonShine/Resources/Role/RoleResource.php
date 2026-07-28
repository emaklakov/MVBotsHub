<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Role;

use App\Models\Role;
use App\MoonShine\Resources\Concerns\HasPerPageSession;
use App\MoonShine\Resources\Role\Pages\RoleIndexPage;
use App\MoonShine\Resources\Role\Pages\RoleFormPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * Класс RoleResource представляет ресурс для работы с ролями пользователей.
 * Определяет заголовок, компоненты и другие параметры ресурса.
 */
class RoleResource extends ModelResource
{
    use HasPerPageSession;

    protected bool $withPolicy = true;

    protected string $model = Role::class;

    protected string $title = 'Роли';

    public function perPageValues(): array
    {
        return [
            6 => 6,
            12 => 12,
            26 => 26,
        ];
    }

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
