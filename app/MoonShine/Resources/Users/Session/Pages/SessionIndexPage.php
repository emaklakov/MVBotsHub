<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\Session\Pages;

use App\Application\Services\User\DeviceDetector;
use App\MoonShine\Resources\Base\BaseIndexPage;
use App\MoonShine\Resources\Users\Session\SessionResource;
use App\MoonShine\Resources\Users\User\UserResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends IndexPage<SessionResource>
 */
class SessionIndexPage extends BaseIndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            BelongsTo::make('Пользователь', 'user', formatted: 'email', resource: UserResource::class)->sortable(),
            ID::make(),
            Text::make('IP', 'ip_address')->sortable(),
            Text::make('Устройство', 'user_agent')
                ->changePreview(fn (?string $value, Text $field) => DeviceDetector::detect($value)),
            Text::make('User Agent', 'user_agent'),
            Date::make('Последняя активность', 'last_activity')
                ->sortable()
                ->format('d.m.Y H:i:s'),
        ];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make('Пользователь', 'user', formatted: 'email', resource: UserResource::class)->nullable(),
            Text::make('IP', 'ip_address')
        ];
    }
}
