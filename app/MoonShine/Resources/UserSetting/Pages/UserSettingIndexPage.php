<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSetting\Pages;

use App\Models\Admin\User\UserSetting;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\UserSetting\UserSettingResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Text;
use Throwable;


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
