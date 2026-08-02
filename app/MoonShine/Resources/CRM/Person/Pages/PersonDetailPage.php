<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CRM\Person\Pages;

use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\CRM\Person\PersonResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends DetailPage<PersonResource>
 */
class PersonDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Телефон', 'phone'),
        ];
    }
}
