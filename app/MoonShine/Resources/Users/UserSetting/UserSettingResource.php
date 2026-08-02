<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\UserSetting;

use App\Models\Users\UserSetting;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Users\UserSetting\Pages\UserSettingDetailPage;
use App\MoonShine\Resources\Users\UserSetting\Pages\UserSettingFormPage;
use App\MoonShine\Resources\Users\UserSetting\Pages\UserSettingIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

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
