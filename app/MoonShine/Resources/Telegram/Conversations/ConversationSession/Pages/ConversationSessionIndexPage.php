<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\ConversationSession\Pages;

use App\Domain\Conversations\Enums\ConversationSessionStatus;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Conversations\ConversationSession\ConversationSessionResource;
use App\MoonShine\Resources\Telegram\Flows\FlowVersion\FlowVersionResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<ConversationSessionResource>
 */
class ConversationSessionIndexPage extends BaseIndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Preview::make('Пользователь', null, fn($item) =>
                $item->subscriber?->telegram_username
                ?? $item->subscriber?->telegram_id
                ?? '—'
            ),
            BelongsTo::make('Версия потока', 'flowVersion', resource: FlowVersionResource::class, formatted: 'version_number'),
            Text::make('Текущий блок', 'current_block_id'),
            Enum::make('Статус', 'status')->attach(ConversationSessionStatus::class),
            Date::make('Истекает', 'expires_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
