<?php

namespace App\MoonShine\Resources\Base;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use Throwable;

class BaseFormPage extends FormPage
{
    protected function buttons(): ListOf
    {
        return parent::buttons()->prepend(
            ActionButton::make('', fn() => $this->getResource()->getIndexPageUrl())
                ->class('btn-square')
                ->icon('arrow-uturn-left'),
        );
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons()->add(
            ActionButton::make('Отмена', fn() => $this->getResource()->getIndexPageUrl())->class('btn-lg'),
        );
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }

    /**
     * @param  FormBuilder  $component
     *
     * @return FormBuilder
     */
    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
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
