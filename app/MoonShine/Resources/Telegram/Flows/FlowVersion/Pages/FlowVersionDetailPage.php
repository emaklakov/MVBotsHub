<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Flows\FlowVersion\Pages;

use App\Domain\Flows\Enums\FlowVersionStatus;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Telegram\Flows\Flow\FlowResource;
use App\MoonShine\Resources\Telegram\Flows\FlowVersion\FlowVersionResource;
use App\MoonShine\Resources\Users\User\UserResource;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;


/**
 * @extends DetailPage<FlowVersionResource>
 */
class FlowVersionDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Основной поток', 'flow', resource: FlowResource::class, formatted: 'name'),
            Text::make('Версия', 'version_number'),
            Enum::make('Статут', 'status')->attach(FlowVersionStatus::class),
            Date::make('Опубликован', 'published_at')
                ->format('d.m.Y H:i:s'),
            BelongsTo::make('Кто опубликовал', 'publisher', resource: UserResource::class, formatted: 'email'),
            Json::make('Схема', 'schema')->fields([
                Text::make('start_block_id', 'start_block_id'),
                Json::make('blocks', 'blocks'),
            ]),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
