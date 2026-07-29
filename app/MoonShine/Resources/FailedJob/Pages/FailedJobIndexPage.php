<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\FailedJob\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\HiddenIds;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\FailedJob\FailedJobResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<FailedJobResource>
 */
class FailedJobIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('UUID', 'uuid'),
            Text::make('Connection', 'connection'),
            Text::make('Очередь', 'queue'),
            Date::make('Failed', 'failed_at')->format('d.m.Y H:i:s'),
        ];
    }

    /**
     * @return ListOf<ActionButtonContract>
     */
    protected function buttons(): ListOf
    {
        return parent::buttons()->add(
            ActionButton::make('Повторить')
                ->icon('arrow-path')
                ->bulk()
                ->inModal(
                    title: 'Повторить выбранные задачи?',
                    content: fn (): string => (string) FormBuilder::make(
                        route('moonshine.failed-jobs.mass-retry'),
                    )
                        ->fields([
                            HiddenIds::make($this->getResource()->getListComponentName()),
                            FlexibleRender::make('Вы уверены, что хотите повторить выполнение выбранных задач?'),
                        ])
                        ->submit('Повторить'),
                ),
        );
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Text::make('Очередь', 'queue'),
            Text::make('Connection', 'connection'),
        ];
    }
}
