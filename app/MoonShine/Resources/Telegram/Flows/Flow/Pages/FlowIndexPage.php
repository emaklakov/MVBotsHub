<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Flows\Flow\Pages;

use App\Domain\Flows\Enums\FlowStatus;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Flows\Flow\FlowResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<FlowResource>
 */
class FlowIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username'),
            Text::make('Название', 'name'),
            Preview::make('Триггер', null, fn($item) => "{$item->trigger_type}: {$item->trigger_value}"),
            Enum::make('Статут', 'status')->attach(FlowStatus::class),
            Preview::make('Опубликовано', null, fn($item) => $item->latestPublishedVersion?->version_number ?? '—'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
