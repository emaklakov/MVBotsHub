<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\Pages;

use App\Domain\CRM\Models\Person;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\CRM\Person\PersonResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\BotSubscriber\Pages\UserResource;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\BotSubscriberResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
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
            BelongsTo::make('Телефон', 'person', resource: PersonResource::class, formatted: 'person.phone'),
            Text::make('Language', 'language'),
            Select::make('Status', 'status')
                ->options([
                    'active' => 'Active',
                    'blocked' => 'Blocked',
                    'merged' => 'Merged',
                ]),
            Date::make('Last Activity', 'last_activity_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
