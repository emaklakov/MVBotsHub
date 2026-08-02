<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\Bot\Pages;

use App\Domain\Bots\Enums\BotStatus;
use App\Domain\Bots\Enums\WebhookStatus;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<BotResource>
 */
class BotIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Имя пользователя', 'username'),
            Text::make('Название бота', 'name'),
            Enum::make('Статус', 'status')->attach(BotStatus::class),
            Enum::make('Webhook', 'webhook_status')->attach(WebhookStatus::class)->disabled(),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
