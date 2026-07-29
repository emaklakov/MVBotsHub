<?php

namespace App\MoonShine\Resources\Base;

use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\TableRowContract;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\Support\ListOf;
use MoonShine\UI\Collections\TableCells;
use MoonShine\UI\Collections\TableRows;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Select;
use Throwable;

class BaseIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return ListOf<ActionButtonContract>
     */
    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function filters(): iterable
    {
        return [];
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
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }

    protected function topRightButtons(): ListOf
    {
        return parent::topRightButtons();
    }

    protected function topLeftButtons(): ListOf
    {
        return parent::topLeftButtons()
            ->add(
                ActionButton::make('', '#')
                    ->class('min-h-[32px]')
                    ->icon('arrow-path')
                    ->dispatchEvent(
                        AlpineJs::event(
                            JsEvent::TABLE_UPDATED,
                            $this->getResource()->getListComponentName()
                        )
                    ),
            );
    }

    protected function modifyCreateButton(ActionButtonContract $button): ActionButtonContract
    {
        return $button->setLabel('');
    }

    protected function modifyFiltersButton(ActionButtonContract $button): ActionButtonContract
    {
        return $button->icon('funnel')->setLabel('');
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component
            ->columnSelection()
            ->topRight(function (): array {
                return [
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
}
