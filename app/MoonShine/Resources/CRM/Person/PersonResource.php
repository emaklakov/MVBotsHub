<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\CRM\Person;

use App\Domain\CRM\Models\Person;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\CRM\Person\Pages\PersonDetailPage;
use App\MoonShine\Resources\CRM\Person\Pages\PersonFormPage;
use App\MoonShine\Resources\CRM\Person\Pages\PersonIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Person, PersonIndexPage, PersonFormPage, PersonDetailPage>
 */
class PersonResource extends BaseResource
{
    protected string $model = Person::class;

    protected string $title = 'Люди';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            PersonIndexPage::class,
            PersonFormPage::class,
            PersonDetailPage::class,
        ];
    }
}
