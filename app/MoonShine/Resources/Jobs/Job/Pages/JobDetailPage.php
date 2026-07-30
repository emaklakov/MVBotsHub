<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Jobs\Job\Pages;

use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Jobs\Job\JobResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;


/**
 * @extends DetailPage<JobResource>
 */
class JobDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Очередь', 'queue'),
            Number::make('Попытки', 'attempts'),
            Json::make('Полезная нагрузка', 'payload'),
            Date::make('Reserved', 'reserved_at')->format('d.m.Y H:i:s'),
            Date::make('Available', 'available_at')->format('d.m.Y H:i:s'),
            Date::make('Создана', 'created_at')->format('d.m.Y H:i:s'),
        ];
    }
}
