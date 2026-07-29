<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSetting\Pages;

use App\Models\Admin\User\UserSetting;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\UserSetting\UserSettingResource;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends DetailPage<UserSettingResource>
 */
class UserSettingDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Пользователь', 'user', resource: UserResource::class, formatted: 'email'),
            Text::make('Название', 'name'),
            Text::make('Ключ', 'key'),
            Checkbox::make('Зашифровано', 'encrypted'),
            Text::make('Значение', 'value', function (UserSetting $item) {
                return $item->encrypted ? '••••••••' : $item->value;
            }),
        ];
    }
}
