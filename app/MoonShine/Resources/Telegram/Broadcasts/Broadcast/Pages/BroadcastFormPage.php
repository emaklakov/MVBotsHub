<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\Pages;

use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Flows\Enums\FlowVersionStatus;
use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\BroadcastResource;
use App\MoonShine\Resources\Telegram\Flows\FlowVersion\FlowVersionResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends FormPage<BroadcastResource>
 */
class BroadcastFormPage extends BaseFormPage
{
    /**
     * @return FieldContract
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название', 'name')->required(),
                BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username')->nullable()->required(),
                BelongsTo::make('Поток', 'flowVersion', resource: FlowVersionResource::class, formatted: fn ($item) => $item->flow?->name.' ('.$item->version_number.')')
                    ->valuesQuery(fn ($query) => $query->where('status', FlowVersionStatus::PUBLISHED))
                    ->nullable()
                    ->required(),
                Date::make('Запланировано', 'scheduled_at')
                    ->withTime()
                    ->required(),
                Enum::make('Статус', 'status')->attach(BroadcastStatus::class)->default(BroadcastStatus::DRAFT)->required(),
            ]),
        ];
    }
}
