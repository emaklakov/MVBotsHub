<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CRM\Person\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\CRM\Person\PersonResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;


/**
 * @extends IndexPage<PersonResource>
 */
class PersonIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
        ];
    }
}
