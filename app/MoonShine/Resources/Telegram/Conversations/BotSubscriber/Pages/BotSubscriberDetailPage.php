<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\Pages;

use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\BotSubscriberResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\ID;


/**
 * @extends DetailPage<BotSubscriberResource>
 */
class BotSubscriberDetailPage extends BaseDetailPage
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
