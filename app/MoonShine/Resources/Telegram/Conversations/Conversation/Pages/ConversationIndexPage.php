<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\Conversation\Pages;

use App\Domain\Conversations\Enums\ConversationStatus;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Conversations\Conversation\ConversationResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;


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
            Enum::make('Статус', 'status')->attach(ConversationStatus::class),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
