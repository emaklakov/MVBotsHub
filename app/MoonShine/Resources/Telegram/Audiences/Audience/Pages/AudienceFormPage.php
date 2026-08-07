<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Audiences\Audience\Pages;

use App\Domain\Audiences\Enums\AudienceType;
use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Telegram\Audiences\Audience\AudienceResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\BotSubscriberResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;


/**
 * @extends FormPage<AudienceResource>
 */
class AudienceFormPage extends BaseFormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название', 'name')->required(),
                BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username')->required(),
                Enum::make('Тип', 'type')->attach(AudienceType::class)->default(AudienceType::STATIC)->required()
                    ->reactive(), // показываем разные поля ниже в зависимости от типа
            ]),

            Box::make('Ручной список', [
                BelongsToMany::make('Подписчики', 'subscribers', resource: BotSubscriberResource::class, formatted: 'telegram_username')
                    ->selectMode(),
            ])->canSee(fn () => !$this->getItem() || $this->getItem()?->type === AudienceType::STATIC),

            Box::make('Условия сегмента', [
                Text::make('Язык (ru/en/…)', 'filters.language')->hint('Пусто — любой язык'),
                Number::make('Активны за последние N дней', 'filters.active_within_days'),
                Number::make('Подписаны минимум N дней назад', 'filters.subscribed_days_ago'),
                Text::make('Тег (settings.tag)', 'filters.settings_tag'),
            ])->canSee(fn () => !$this->getItem() || $this->getItem()?->type === AudienceType::DYNAMIC),
        ];
    }
}
