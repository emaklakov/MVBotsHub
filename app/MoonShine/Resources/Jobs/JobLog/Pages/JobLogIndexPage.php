<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Jobs\JobLog\Pages;

use App\Domain\Queue\Enums\JobLogStatus;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Jobs\JobLog\JobLogResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<JobLogResource>
 */
class JobLogIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Задача', 'name'),
            Text::make('Очередь', 'queue'),
            Enum::make('Статус', 'status')->attach(JobLogStatus::class),
            Text::make('Продолжительность (сек)', 'duration'),
            Date::make('Началась', 'started_at')->format('d.m.Y H:i:s'),
            Date::make('Закончилась', 'finished_at')->format('d.m.Y H:i:s'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Text::make('Status', 'status'),
            Text::make('Queue', 'queue'),
            Text::make('Job', 'name'),
        ];
    }
}
