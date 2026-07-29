<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserLog\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\UserLog\UserLogResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends IndexPage<UserLogResource>
 */
class UserLogIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Date::make('Дата', 'created_at')->format('d.m.Y H:i:s')->sortable(),
            Text::make('Пользователь', 'user.name')->sortable(),
            Text::make('Действие', 'action')->sortable(),
            Text::make('Объект', 'subject_type')
                ->changePreview(fn ($value, $field) => $value
                    ? class_basename($value) . ' #' . $field->getData()->subject_id
                    : '—'),
            Text::make('IP', 'ip_address'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Text::make('Действие', 'action'),
            Text::make('IP', 'ip_address'),
        ];
    }
}
