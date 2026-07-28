<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Notification\Pages;

use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\TableRowContract;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\UI\Collections\TableCells;
use MoonShine\UI\Collections\TableRows;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Badge;
use MoonShine\Support\Enums\Color;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\Notification\NotificationResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends IndexPage<NotificationResource>
 */
class NotificationIndexPage extends IndexPage
{
    protected bool $isLazy = true;

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
     * @return ListOf<ActionButtonContract>
     */
    protected function buttons(): ListOf
    {
        return parent::buttons();
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

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component
//            ->sticky()
//            ->stickyButtons()
            ->columnSelection()
            ->topRight(function (): array {
                return [
                    Div::make([
                        ActionButton::make('', '#')
                            ->icon('arrow-path')
                            ->class('py-3')
                            ->dispatchEvent(
                                AlpineJs::event(
                                    JsEvent::TABLE_UPDATED,
                                    $this->getResource()->getListComponentName()
                                )
                            ),
                    ]),
                    Div::make([
                        Select::make('Per page')
                            ->onChangeMethod(
                                'changeListingComponentState',
                                params: ['state' => 'perPage'],
                                page: $this, // <-- явно указываем текущую страницу
                            )
                            ->options($this->getResource()->perPageValues())
                            ->withoutWrapper()
                            ->native()
                            ->setValue($this->getResource()->getItemsPerPage()),
                    ]),
                ];
            })
            ->footRows(
                function (?TableRowContract $default) use ($component) {
                    $paginator = $component->getPaginator();
                    $total = $paginator?->getTotal() ?? 0;

                    return TableRows::make([$default])->pushRow(
                        TableCells::make()
                            ->pushCell('')
                            ->pushCell("Всего: {$total}")
                            ->pushCell('')
                            ->pushCell('')
                            ->pushCell('')
                            ->pushCell('')
                    );
                }
            );
    }

    #[AsyncMethod]
    public function changeListingComponentState(
        CrudRequestContract $request,
        JsonResponse $response,
    ): JsonResponse {
        if ($request->input('state') === 'perPage') {
            session()->put(
                $this->getResource()->perPageSessionKey(),
                $request->input('value'),
            );

            return $response->events([
                AlpineJs::event(
                    JsEvent::TABLE_UPDATED,
                    $this->getResource()->getListComponentName(),
                ),
                AlpineJs::event(
                    JsEvent::CARDS_UPDATED,
                    $this->getResource()->getListComponentName(),
                ),
            ]);
        }

        if ($request->input('state') === 'view') {
            session()->put($request->input('state'), $request->input('value'));

            return $response->redirect($this->getResource()->getIndexPageUrl());
        }

        return $response->redirect($this->getResource()->getIndexPageUrl());
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
