<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Job\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\Job\JobResource;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<JobResource>
 */
class JobIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Очередь', 'queue'),
            Number::make('Попытки', 'attempts'),
            Date::make('Reserved', 'reserved_at')->format('d.m.Y H:i:s'),
            Date::make('Available', 'available_at')->format('d.m.Y H:i:s'),
            Date::make('Создана', 'created_at')->format('d.m.Y H:i:s'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Text::make('Очередь', 'queue'),
        ];
    }
}
