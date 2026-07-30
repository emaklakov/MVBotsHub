<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\UserLog\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Users\UserLog\UserLogResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


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
