<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient\Pages;

use App\Domain\Broadcasts\Enums\BroadcastRecipientStatus;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient\BroadcastRecipientResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<BroadcastRecipientResource>
 */
class BroadcastRecipientIndexPage extends BaseIndexPage
{
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
            Enum::make('Статус', 'status')->attach(BroadcastRecipientStatus::class),
            Date::make('Дата отправки', 'sent_at')
                ->format('d.m.Y H:i:s'),
            Text::make('Ошибка', 'error'),
            Text::make('Попытки', 'attempts'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
