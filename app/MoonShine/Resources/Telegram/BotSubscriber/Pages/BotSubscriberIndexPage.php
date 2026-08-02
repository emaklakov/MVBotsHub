<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\BotSubscriber\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\BotSubscriber\BotSubscriberResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<BotSubscriberResource>
 */
class BotSubscriberIndexPage extends BaseIndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Bot', 'bot', resource: BotResource::class),
            Text::make('Telegram ID', 'telegram_id'),
            Text::make('Username', 'telegram_username'),
        ];
    }
}
