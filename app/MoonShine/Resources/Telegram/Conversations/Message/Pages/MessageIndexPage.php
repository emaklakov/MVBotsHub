<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\Message\Pages;

use App\Domain\Conversations\Models\BotSubscriber;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Conversations\Conversation\ConversationResource;
use App\MoonShine\Resources\Telegram\Conversations\Message\MessageResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<MessageResource>
 */
class MessageIndexPage extends BaseIndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Направление', 'direction'),
            BelongsTo::make('Пользователь', 'conversation', resource: ConversationResource::class, formatted: fn ($item) => $item->subscriber?->telegram_id),
            Text::make('Тип', 'type'),
            Preview::make('Содержание', null, fn($item) =>
                $item->content['text']
                ?? ($item->content['file_id'] ?? json_encode($item->content))
            ),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
