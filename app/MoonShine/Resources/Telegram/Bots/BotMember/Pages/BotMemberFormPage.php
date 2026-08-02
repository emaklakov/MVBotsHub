<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\BotMember\Pages;

use App\Domain\Bots\Enums\BotMemberRole;
use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Telegram\Bots\BotMember\BotMemberResource;
use App\MoonShine\Resources\Users\User\UserResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;


/**
 * @extends FormPage<BotMemberResource>
 */
class BotMemberFormPage extends BaseFormPage
{
    /**
     * @return FieldContract
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Бот', 'bot', resource: UserResource::class, formatted: 'username')->nullable(),
            Enum::make('Роль', 'role')->attach(BotMemberRole::class)->nullable(),
            BelongsTo::make('Пользователь', 'user', resource: UserResource::class, formatted: 'email')->nullable(),
        ];
    }
}
