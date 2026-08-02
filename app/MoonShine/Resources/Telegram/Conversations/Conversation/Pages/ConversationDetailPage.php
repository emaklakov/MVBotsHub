<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\Conversation\Pages;

use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Telegram\Conversations\Conversation\ConversationResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends DetailPage<ConversationResource>
 */
class ConversationDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Preview::make('Bot', null, fn($item) => $item->subscriber?->bot?->username ?? '—'),
            Preview::make('Subscriber', null, fn($item) =>
                $item->subscriber?->telegram_username
                ?? $item->subscriber?->telegram_id
                ?? '—'
            ),
            Text::make('Status'),
            Json::make('Context', 'context'),
            Date::make('Created', 'created_at'),
        ];
    }
}
