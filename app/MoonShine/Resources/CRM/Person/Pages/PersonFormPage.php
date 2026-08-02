<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CRM\Person\Pages;

use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\CRM\Person\PersonResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends FormPage<PersonResource>
 */
class PersonFormPage extends BaseFormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Телефон', 'phone'),
            ]),
        ];
    }
}
