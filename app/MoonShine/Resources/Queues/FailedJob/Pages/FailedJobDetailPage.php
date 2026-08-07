<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Queues\FailedJob\Pages;

use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Queues\FailedJob\FailedJobResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends DetailPage<FailedJobResource>
 */
class FailedJobDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('UUID', 'uuid'),
            Text::make('Connection', 'connection'),
            Text::make('Очередь', 'queue'),
            Json::make('Полезная нагрузка', 'payload'),
            Preview::make('Exception', 'exception'),
            Date::make('Failed', 'failed_at')->format('d.m.Y H:i:s'),
        ];
    }
}
