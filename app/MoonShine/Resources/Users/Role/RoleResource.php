<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\Role;

use App\Models\User\Role;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Users\Role\Pages\RoleFormPage;
use App\MoonShine\Resources\Users\Role\Pages\RoleIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * Класс RoleResource представляет ресурс для работы с ролями пользователей.
 * Определяет заголовок, компоненты и другие параметры ресурса.
 */
class RoleResource extends BaseResource
{
    protected string $model = Role::class;

    protected string $title = 'Роли';

    // Отключаем массовое удаление
    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::MASS_DELETE);
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
