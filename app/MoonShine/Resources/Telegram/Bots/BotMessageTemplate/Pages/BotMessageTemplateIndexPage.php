<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\BotMessageTemplate\Pages;

use App\Domain\Bots\Enums\SystemMessageKey;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;

class BotMessageTemplateIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username'),
            Enum::make('Сообщение', 'key')->attach(SystemMessageKey::class),
            Preview::make('Основной', 'translations.basic'),
            Preview::make('RU', 'translations.ru'),
            Preview::make('EN', 'translations.en'),
        ];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username')->nullable(),
            Enum::make('Сообщение', 'key')->attach(SystemMessageKey::class)->nullable(),
        ];
    }
}
