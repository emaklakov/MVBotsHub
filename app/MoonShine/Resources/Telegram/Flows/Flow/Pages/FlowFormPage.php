<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Flows\Flow\Pages;

use App\Domain\Flows\Enums\FlowStatus;
use App\Domain\Flows\Enums\TriggerTypes;
use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Flows\Flow\FlowResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\Enums\PageType;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends FormPage<FlowResource>
 */
class FlowFormPage extends BaseFormPage
{
    protected ?PageType $redirectAfterSave = PageType::DETAIL;

    /**
     * @return FieldContract
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username')
                    ->nullable()
                    ->required(),
                Text::make('Название', 'name')->required(),
                Enum::make('Тип триггера', 'trigger_type')->attach(TriggerTypes::class)->nullable()->required(),
                Text::make('Значение триггера', 'trigger_value')
                    ->required()
                    ->placeholder('start, help, campaign_123...'),
                Enum::make('Статут', 'status')->attach(FlowStatus::class)->default(FlowStatus::DRAFT)->required(),
            ]),
        ];
    }
}
