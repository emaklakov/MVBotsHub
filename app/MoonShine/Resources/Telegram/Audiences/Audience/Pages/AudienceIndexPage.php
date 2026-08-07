<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Audiences\Audience\Pages;

use App\Domain\Audiences\Enums\AudienceType;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Audiences\Audience\AudienceResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<AudienceResource>
 */
class AudienceIndexPage extends BaseIndexPage
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
            Enum::make('Тип', 'type')->attach(AudienceType::class),
            Preview::make('Размер', 'cached_count'),
        ];
    }
}
