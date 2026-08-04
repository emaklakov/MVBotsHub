<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\Pages;

use App\Domain\Conversations\Enums\SubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\CRM\Person\PersonResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\BotSubscriberResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<BotSubscriberResource>
 */
class BotSubscriberIndexPage extends BaseIndexPage
{
    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [
            ValueMetric::make('Активные пользователи')
                ->value(fn() => BotSubscriber::count())
                ->columnSpan(2),
        ];
    }

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
            Enum::make('Статус', 'status')->attach(SubscriberStatus::class),
            Date::make('Последняя активность', 'last_activity_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
