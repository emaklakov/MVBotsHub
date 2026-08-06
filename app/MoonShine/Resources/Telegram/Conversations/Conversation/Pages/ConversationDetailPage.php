<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\Conversation\Pages;

use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Models\ConversationSession;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Telegram\Conversations\Conversation\ConversationResource;
use App\MoonShine\Resources\Telegram\Conversations\ConversationSession\ConversationSessionResource;
use App\MoonShine\Resources\Telegram\Conversations\Message\MessageResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
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
            Preview::make('Бот', null, fn($item) => $item->subscriber?->bot?->username ?? '—'),
            Preview::make('Пользователь', null, fn($item) =>
                $item->subscriber?->telegram_username
                ?? $item->subscriber?->telegram_id
                ?? '—'
            ),
            Enum::make('Статус', 'status')->attach(ConversationStatus::class),
            Json::make('Контекст', 'context'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
            HasMany::make('Сообщения', 'messages', resource: MessageResource::class)
                ->tabMode(),
        ];
    }
}
