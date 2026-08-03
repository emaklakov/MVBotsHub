<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\BotMember\Pages;

use App\Domain\Bots\Enums\BotMemberRole;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Bots\BotMember\BotMemberResource;
use App\MoonShine\Resources\Users\User\UserResource;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;


/**
 * @extends IndexPage<BotMemberResource>
 */
class BotMemberIndexPage extends BaseIndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username'),
            Enum::make('Роль', 'role')->attach(BotMemberRole::class),
            BelongsTo::make('Пользователь', 'user', resource: UserResource::class, formatted: 'email'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')->format('d.m.Y H:i:s'),
            BelongsTo::make('Кто создал', 'createdBy', resource: UserResource::class, formatted: 'email'),
        ];
    }

    protected function modifyEditButton(ActionButtonContract $button): ActionButtonContract
    {
        return $button->canSee(fn() => false);
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username')->nullable(),
            Enum::make('Роль', 'role')->attach(BotMemberRole::class)->nullable(),
            BelongsTo::make('Пользователь', 'user', resource: UserResource::class, formatted: 'email')->nullable(),
            BelongsTo::make('Кто создал', 'createdBy', resource: UserResource::class, formatted: 'email')->nullable(),
        ];
    }
}
