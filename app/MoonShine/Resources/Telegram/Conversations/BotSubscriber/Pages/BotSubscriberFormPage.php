<?php

namespace App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\Pages;

use App\Domain\Conversations\Enums\BotSubscriberStatus;
use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\CRM\Person\PersonResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Support\Enums\PageType;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;
use Symfony\Component\HttpFoundation\Response;

class BotSubscriberFormPage extends BaseFormPage
{
//    protected function modifyResponse(): ?Response
//    {
//        $resource = $this->getResource();
//        $id = $resource->getItemID();
//
//        if ($id) {
//            return redirect()->to($resource->getDetailPageUrl($id));
//        }
//
//        return null;
//    }

    /**
     * @return FieldContract
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username')->disabled(),
            Text::make('Telegram ID', 'telegram_id')->disabled(),
            Text::make('Имя пользователя', 'telegram_username')->disabled(),
            BelongsTo::make('Телефон', 'person', resource: PersonResource::class, formatted: 'phone')->disabled(),
            Enum::make('Статус', 'status')->attach(BotSubscriberStatus::class),
            Json::make('Настройки', 'settings')
                ->keyValue('Ключ', 'Значение')
                ->creatable()   // кнопка «Добавить»
                ->removable()   // кнопка «Удалить»
                ->default([]),
        ];
    }
}
