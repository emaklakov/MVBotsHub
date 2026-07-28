<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\FailedJob\Pages;

use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\TableRowContract;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\UI\Collections\TableCells;
use MoonShine\UI\Collections\TableRows;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\HiddenIds;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\FailedJob\FailedJobResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends IndexPage<FailedJobResource>
 */
class FailedJobIndexPage extends IndexPage
{
    protected bool $isLazy = true;

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

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component
//            ->sticky()
//            ->stickyButtons()
            ->columnSelection()
            ->topRight(function (): array {
                return [
                    Div::make([
                        ActionButton::make('', '#')
                            ->icon('arrow-path')
                            ->class('py-3')
                            ->dispatchEvent(
                                AlpineJs::event(
                                    JsEvent::TABLE_UPDATED,
                                    $this->getResource()->getListComponentName()
                                )
                            ),
                    ]),
                    Div::make([
                        Select::make('Per page')
                            ->onChangeMethod(
                                'changeListingComponentState',
                                params: ['state' => 'perPage'],
                                page: $this, // <-- явно указываем текущую страницу
                            )
                            ->options($this->getResource()->perPageValues())
                            ->withoutWrapper()
                            ->native()
                            ->setValue($this->getResource()->getItemsPerPage()),
                    ]),
                ];
            })
            ->footRows(
                function (?TableRowContract $default) use ($component) {
                    $paginator = $component->getPaginator();
                    $total = $paginator?->getTotal() ?? 0;

                    return TableRows::make([$default])->pushRow(
                        TableCells::make()
                            ->pushCell('')
                            ->pushCell("Всего: {$total}")
                            ->pushCell('')
                            ->pushCell('')
                            ->pushCell('')
                    );
                }
            );
    }

    #[AsyncMethod]
    public function changeListingComponentState(
        CrudRequestContract $request,
        JsonResponse $response,
    ): JsonResponse {
        if ($request->input('state') === 'perPage') {
            session()->put(
                $this->getResource()->perPageSessionKey(),
                $request->input('value'),
            );

            return $response->events([
                AlpineJs::event(
                    JsEvent::TABLE_UPDATED,
                    $this->getResource()->getListComponentName(),
                ),
                AlpineJs::event(
                    JsEvent::CARDS_UPDATED,
                    $this->getResource()->getListComponentName(),
                ),
            ]);
        }

        if ($request->input('state') === 'view') {
            session()->put($request->input('state'), $request->input('value'));

            return $response->redirect($this->getResource()->getIndexPageUrl());
        }

        return $response->redirect($this->getResource()->getIndexPageUrl());
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
