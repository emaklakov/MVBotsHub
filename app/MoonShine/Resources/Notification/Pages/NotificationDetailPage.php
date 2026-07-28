<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Notification\Pages;

use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Badge;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\Notification\NotificationResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Throwable;
use MoonShine\Support\Enums\Color;


/**
 * @extends DetailPage<NotificationResource>
 */
class NotificationDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Тип', 'type'),

            Text::make('Получатель', 'notifiable_type'),
            Text::make('ID получателя', 'notifiable_id'),

            Text::make('Приоритет', 'priority')
                ->badge(fn(string $priority) => match($priority) {
                    'critical' => Color::RED,
                    'high' => Color::WARNING,
                    'normal' => Color::PRIMARY,
                    'low' => Color::GRAY,
                    default => Color::GRAY,
                }),

            Text::make('Категория', 'category'),

            Json::make('Данные', 'data'),

            Preview::make('Статус', 'read_at')
                ->changePreview(fn($readAt) => $readAt ? '✅ Прочитано' : '🔔 Новое'),

            Date::make('Создано', 'created_at')->format('d.m.Y H:i'),
            Date::make('Прочитано', 'read_at')->format('d.m.Y H:i'),
            Date::make('Открыто', 'opened_at')->format('d.m.Y H:i'),
            Date::make('Истекает', 'expires_at')->format('d.m.Y H:i'),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyDetailComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
