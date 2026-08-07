<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\Pages;

use App\Domain\Conversations\Enums\BotSubscriberStatus;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\CRM\Person\PersonResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\BotSubscriberResource;
use App\MoonShine\Resources\Telegram\Conversations\Conversation\ConversationResource;
use App\MoonShine\Resources\Telegram\Conversations\ConversationSession\ConversationSessionResource;
use App\MoonShine\Resources\Telegram\Conversations\Message\MessageResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Fields\Relationships\HasManyThrough;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;


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
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username'),
            Text::make('Telegram ID', 'telegram_id'),
            Text::make('Имя пользователя', 'telegram_username'),
            BelongsTo::make('Телефон', 'person', resource: PersonResource::class, formatted: 'phone'),
            Text::make('Язык', 'language'),
            Enum::make('Статус', 'status')->attach(BotSubscriberStatus::class),
            Date::make('Последняя активность', 'last_activity_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
            Json::make('Настройки', 'settings')
                ->keyValue('Ключ', 'Значение')
                ->creatable()   // кнопка «Добавить»
                ->removable()   // кнопка «Удалить»
                ->default([]),
            HasManyThrough::make('Сообщения', 'messages', resource: MessageResource::class)
                ->tabMode(),
            HasMany::make('Диалоги', 'conversations', resource: ConversationResource::class)
                ->tabMode(),
            HasMany::make('Сессии диалогов', 'conversationSessions', resource: ConversationSessionResource::class)
                ->tabMode(),
        ];
    }
}
