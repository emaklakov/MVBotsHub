<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSetting;

use App\Models\Admin\User\UserSetting;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\UserSetting\Pages\UserSettingIndexPage;
use App\MoonShine\Resources\UserSetting\Pages\UserSettingFormPage;
use App\MoonShine\Resources\UserSetting\Pages\UserSettingDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<UserSetting, UserSettingIndexPage, UserSettingFormPage, UserSettingDetailPage>
 */
class UserSettingResource extends BaseResource
{
    protected string $model = UserSetting::class;

    protected string $title = 'Настройки пользователей';

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
