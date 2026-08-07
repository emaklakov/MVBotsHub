<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\BotMessageTemplate\Pages;

use App\Domain\Bots\Enums\SystemMessageKey;
use App\MoonShine\Resources\Base\BaseFormPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

class BotMessageTemplateFormPage extends BaseFormPage
{
    /**
     * Бот и ключ сообщения не редактируются здесь — строки заводит
     * BotObserver при создании бота (см. BotMessageTemplateResource).
     * Показаны как Preview (не форм-виджеты), а не BelongsTo/Enum
     * с ->disabled(), чтобы не упереться в известную проблему рендеринга
     * disabled-полей BelongsTo в MoonShine.
     *
     * @return FieldContract
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Preview::make('Бот', null, fn ($item) => $item->bot?->username ?? '—'),
                Preview::make('Сообщение', null, fn ($item) => $item->key instanceof SystemMessageKey ? $item->key->label() : (string) $item->key),
            ]),

            Box::make('Переводы', [
                Json::make('', 'translations')
                    ->object()
                    ->fields([
                        Textarea::make('Основной', 'basic')
                            ->hint('Если пусто — используется встроенный дефолт платформы'),
                        Textarea::make('Русский', 'ru')
                            ->hint('Если пусто — используется встроенный дефолт платформы'),
                        Textarea::make('English', 'en')
                            ->hint('Если пусто — используется встроенный дефолт платформы'),
                    ]),
            ]),
        ];
    }
}
