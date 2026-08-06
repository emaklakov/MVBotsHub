<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\Pages;

use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\BroadcastResource;
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
 * @extends IndexPage<BroadcastResource>
 */
class BroadcastIndexPage extends BaseIndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name'),
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username'),
            BelongsTo::make('Поток', 'flowVersion', resource: FlowVersionResource::class, formatted: fn ($item) => $item->flow?->name.' ('.$item->version_number.')'),
            Enum::make('Статус', 'status')->attach(BroadcastStatus::class),
            Preview::make('Процесс', null, fn($item) => "{$item->sent_count}/{$item->total_recipients} ({$item->failed_count} failed)"),
            Date::make('Запланировано', 'scheduled_at')
                ->format('d.m.Y H:i:s'),
            Date::make('Запущено', 'started_at')
                ->format('d.m.Y H:i:s'),
            Date::make('Завершено', 'completed_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
