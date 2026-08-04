<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\Bot\Pages;

use App\Domain\Bots\Enums\BotStatus;
use App\Domain\Bots\Enums\WebhookStatus;
use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Bots\BotMember\BotMemberResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\Position;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;


/**
 * @extends FormPage<BotResource>
 */
class BotFormPage extends BaseFormPage
{
    /**
     * @return FieldContract
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make()->sortable(),
                Text::make('Имя пользователя', 'username')
                    ->placeholder('@username_bot')
                    ->required(),
                Text::make('Название бота', 'name'),
                Textarea::make('Описание', 'description'),
                Preview::make('Статус токена', 'bot_token_status', fn($item) => $item->maskedTokenPreview())
                    ->badge(fn($value) => $value == 'set' ? 'green' : 'gray'),
                Password::make('Токен Бота', 'token')
                    ->raw(true)
                    ->placeholder('123456:ABC-DEF...')
                    ->required(fn() => is_null($this->getItem()))
                    ->hint('Оставьте пустым, чтобы не менять текущий токен')
                    ->onApply(function ($item, $value, $field) {
                        if (empty($value)) {
                            return $item;
                        }

                        $item->token = $value;
                        return $item;
                    })
                    ->customAttributes([
                        'autocomplete' => 'off',
                    ]),
                Json::make('Настройки', 'settings')
                    ->fields([
                        Position::make(),
                        Text::make('Имя', 'name'),
                        Text::make('Ключ', 'key'),
                        Text::make('Значение', 'value'),
                        Json::make('Данные', 'data')
                            ->keyValue(),
                        Switcher::make('Включено', 'is_active'),
                    ])
                    ->default([]),
                Enum::make('Статус', 'status')->attach(BotStatus::class)
                    ->default(BotStatus::DISABLED)
                    ->required(),
                HasMany::make('Доступы к боту', 'members', resource: BotMemberResource::class)
                    ->creatable()
                    ->tabMode(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'token' => [
                $item->getKey() === null ? 'required' : 'nullable',
                'string',
            ],
            'status' => ['required', 'in:active,paused,disabled,archived'],
        ];
    }
}
