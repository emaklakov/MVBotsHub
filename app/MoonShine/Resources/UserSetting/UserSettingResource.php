<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSetting;

use App\Models\Admin\User\UserSetting;
use App\MoonShine\Resources\Concerns\HasPerPageSession;
use App\MoonShine\Resources\UserSetting\Pages\UserSettingIndexPage;
use App\MoonShine\Resources\UserSetting\Pages\UserSettingFormPage;
use App\MoonShine\Resources\UserSetting\Pages\UserSettingDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<UserSetting, UserSettingIndexPage, UserSettingFormPage, UserSettingDetailPage>
 */
class UserSettingResource extends ModelResource
{
    use HasPerPageSession;

    protected bool $withPolicy = true;

    protected string $model = UserSetting::class;

    protected string $title = 'Настройки пользователей';

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
            UserSettingIndexPage::class,
            UserSettingFormPage::class,
            UserSettingDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'name',
            'key',
            'value'
        ];
    }
}
