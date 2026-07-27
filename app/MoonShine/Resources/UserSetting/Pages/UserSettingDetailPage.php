<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSetting\Pages;

use App\Models\Admin\User\UserSetting;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\UserSetting\UserSettingResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends DetailPage<UserSettingResource>
 */
class UserSettingDetailPage extends DetailPage
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

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyDetailComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
