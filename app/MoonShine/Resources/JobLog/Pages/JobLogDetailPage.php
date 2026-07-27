<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\JobLog\Pages;

use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Badge;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\JobLog\JobLogResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends DetailPage<JobLogResource>
 */
class JobLogDetailPage extends DetailPage
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
