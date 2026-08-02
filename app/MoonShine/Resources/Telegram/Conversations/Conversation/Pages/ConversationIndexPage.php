<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\Conversation\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\BotSubscriberResource;
use App\MoonShine\Resources\Telegram\Conversations\Conversation\ConversationResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<ConversationResource>
 */
class ConversationIndexPage extends BaseIndexPage
{

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Preview::make('Bot', null, fn($item) => $item->subscriber?->bot?->username ?? '—'),
            Preview::make('Subscriber', null, fn($item) =>
                $item->subscriber?->telegram_username
                ?? $item->subscriber?->telegram_id
                ?? '—'
            ),
            Text::make('Status'),
            Date::make('Created', 'created_at'),
        ];
    }
}
