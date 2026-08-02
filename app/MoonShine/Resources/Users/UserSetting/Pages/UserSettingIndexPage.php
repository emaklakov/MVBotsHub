<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\UserSetting\Pages;

use App\Models\Users\UserSetting;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Users\User\UserResource;
use App\MoonShine\Resources\Users\UserSetting\UserSettingResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<UserSettingResource>
 */
class UserSettingIndexPage extends BaseIndexPage
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

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            BelongsTo::make('Пользователь', 'user', resource: UserResource::class, formatted: 'email')->nullable(),
            Text::make('Название', 'name'),
            Text::make('Ключ', 'key'),
            Text::make('Значение', 'value'),
        ];
    }
}
