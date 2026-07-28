<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\User\Pages;

use App\MoonShine\Resources\Permission\PermissionResource;
use App\MoonShine\Resources\Role\RoleResource;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\TableRowContract;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\UI\Collections\TableCells;
use MoonShine\UI\Collections\TableRows;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use Throwable;
use MoonShine\Support\Attributes\AsyncMethod;


/**
 * @extends IndexPage<UserResource>
 */
class UserIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Switcher::make('Активный', 'is_active')->sortable(),
            Text::make(__('moonshine::ui.resource.name'), 'name'),
            Email::make(__('moonshine::ui.resource.email'), 'email')
                ->sortable(),
            BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')->inLine(separator: ', '),
            BelongsToMany::make('Разрешения', 'permissions', resource: PermissionResource::class, formatted: 'name')->inLine(separator: ', '),
            Switcher::make('Включен: 2FA', 'enabled_2fa')->sortable(),
            Switcher::make('Включен: Несколько устройств', 'enabled_multi_device_login')->sortable(),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s')
                ->sortable(),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s')
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
            Checkbox::make('Активный', 'is_active'),
            Number::make('ID', 'id'),
            Text::make(__('moonshine::ui.resource.name'), 'name'),
            Email::make(__('moonshine::ui.resource.email'), 'email'),
            BelongsToMany::make('Роли', 'roles', resource: RoleResource::class, formatted: 'name')
                ->selectMode()
                ->nullable(),
            BelongsToMany::make('Разрешения', 'permissions', resource: PermissionResource::class, formatted: 'name')
                ->selectMode()
                ->nullable(),
            Checkbox::make('Включен: 2FA', 'enabled_2fa'),
            Checkbox::make('Включен: Несколько устройств', 'enabled_multi_device_login'),
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
//            ActionButton::make('Обновить', '#')
//                ->icon('arrow-path')
//                ->dispatchEvent(
//                    AlpineJs::event(
//                        JsEvent::TABLE_UPDATED,
//                        $this->getResource()->getListComponentName()
//                    )
//                ),
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
