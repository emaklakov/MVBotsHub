<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\JobLog\Pages;

use App\MoonShine\Resources\Base\BaseDetailPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\JobLog\JobLogResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;

/**
 * @extends DetailPage<JobLogResource>
 */
class JobLogDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('ID Задачи', 'job_id'),
            Text::make('Задача', 'name'),
            Text::make('Очередь', 'queue'),

            Text::make('Статус', 'status')
                ->badge(fn(string $status) => match($status) {
                    'completed'  => 'success',
                    'failed'     => 'error',
                    'processing' => 'warning',
                    default      => 'gray',
                }),

            Text::make('Попытки', 'attempts'),
            Text::make('Продолжительность (сек)', 'duration'),
            Json::make('Полезная нагрузка', 'payload'),
            Text::make('Ошибка', 'error'),
            Date::make('Началась', 'started_at'),
            Date::make('Закончилась', 'finished_at'),
            Date::make('Создана', 'created_at'),
        ];
    }
}
