<?php

namespace App\MoonShine\Resources\Permission;

use App\MoonShine\Resources\Concerns\HasPerPageSession;
use App\MoonShine\Resources\Permission\Pages\PermissionFormPage;
use App\MoonShine\Resources\Permission\Pages\PermissionIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use Spatie\Permission\Models\Permission;

/**
 * Класс PermissionResource представляет ресурс для работы с разрешениями пользователей.
 * Определяет заголовок, компоненты и другие параметры ресурса.
 */
class PermissionResource extends ModelResource
{
    use HasPerPageSession;

    protected bool $withPolicy = true;

    protected string $model = Permission::class;

    protected string $title = 'Разрешения';

    public function perPageValues(): array
    {
        return [
            6 => 6,
            12 => 12,
            26 => 26,
        ];
    }

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
