<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Notification\Pages;

use App\MoonShine\Resources\Base\BaseIndexPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Support\Enums\Color;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\Notification\NotificationResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends IndexPage<NotificationResource>
 */
class NotificationIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Получатель', 'notifiable_type')
                ->changePreview(fn(string $type) => class_basename($type)),

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

            Preview::make('Сообщение', 'data')
                ->changePreview(fn(array $data) => $data['message'] ?? '-'),

            Preview::make('Статус', 'read_at')
                ->changePreview(fn($readAt) => $readAt ? '✅ Прочитано' : '🔔 Новое'),

            Date::make('Создано', 'created_at')
                ->format('d.m.Y H:i')
                ->sortable(),

            Date::make('Прочитано', 'read_at')
                ->format('d.m.Y H:i')
                ->sortable(),

            Date::make('Открыто', 'opened_at')
                ->format('d.m.Y H:i')
                ->sortable(),

            Date::make('Истекает', 'expires_at')
                ->format('d.m.Y H:i')
                ->sortable(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Select::make('Приоритет', 'priority')
                ->options([
                    'critical' => 'Критический',
                    'high' => 'Высокий',
                    'normal' => 'Обычный',
                    'low' => 'Низкий',
                ])
                ->nullable(),

            Select::make('Категория', 'category')
                ->options([
                    'orders.new' => 'Заказы',
                    'users.registered' => 'Пользователи',
                    'system.errors' => 'Система',
                ])
                ->nullable(),

            Select::make('Статус', 'read_at')
                ->options([
                    '1' => 'Прочитано',
                    '0' => 'Не прочитано',
                ])
                ->nullable()
                ->onApply(function ($query, $value) {
                    return $value === '1'
                        ? $query->whereNotNull('read_at')
                        : $query->whereNull('read_at');
                }),

            Date::make('Создано с', 'created_at')
                ->onApply(fn($query, $value) => $query->whereDate('created_at', '>=', $value)),

            Date::make('Создано по', 'created_at')
                ->onApply(fn($query, $value) => $query->whereDate('created_at', '<=', $value)),
        ];
    }
}
