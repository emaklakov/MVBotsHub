<?php

namespace App\MoonShine\Resources\Users\Permission;

use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Users\Permission\Pages\PermissionFormPage;
use App\MoonShine\Resources\Users\Permission\Pages\PermissionIndexPage;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use Spatie\Permission\Models\Permission;

/**
 * Класс PermissionResource представляет ресурс для работы с разрешениями пользователей.
 * Определяет заголовок, компоненты и другие параметры ресурса.
 */
class PermissionResource extends BaseResource
{
    protected string $model = Permission::class;

    protected string $title = 'Разрешения';

    // Отключаем массовое удаление
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
