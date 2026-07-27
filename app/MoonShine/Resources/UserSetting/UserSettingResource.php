<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSetting;

use App\Models\Admin\User\UserSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use App\MoonShine\Resources\UserSetting\Pages\UserSettingIndexPage;
use App\MoonShine\Resources\UserSetting\Pages\UserSettingFormPage;
use App\MoonShine\Resources\UserSetting\Pages\UserSettingDetailPage;

use Illuminate\Support\Facades\Crypt;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<UserSetting, UserSettingIndexPage, UserSettingFormPage, UserSettingDetailPage>
 */
class UserSettingResource extends ModelResource
{
    protected bool $withPolicy = true;

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
}
