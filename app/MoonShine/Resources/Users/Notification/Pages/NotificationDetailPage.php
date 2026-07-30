<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\Notification\Pages;

use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Users\Notification\NotificationResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\Enums\Color;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends DetailPage<NotificationResource>
 */
class NotificationDetailPage extends BaseDetailPage
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
}
