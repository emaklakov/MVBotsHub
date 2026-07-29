<?php

namespace App\MoonShine\Resources\Base;

use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\Table\TableBuilder;
use Throwable;

class BaseDetailPage extends DetailPage
{
    protected function buttons(): ListOf
    {
        return parent::buttons();
        //return new ListOf(ActionButtonContract::class, []);
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
            ...parent::topLayer(),
            ActionGroup::make(
                $this->getButtons()->prepend(
                    ActionButton::make('', fn() => $this->getResource()->getIndexPageUrl())
                        ->class('btn-square')
                        ->icon('arrow-uturn-left')
                ),
            )->fill($this->getResource()->getCastedData())->class('mb-4'),
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
