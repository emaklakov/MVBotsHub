<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Session\Pages;

use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\User\UserResource;
use App\Services\DeviceDetector;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\Session\SessionResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends DetailPage<SessionResource>
 */
class SessionDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            BelongsTo::make('Пользователь', 'user', formatted: 'email', resource: UserResource::class),
            ID::make(),
            Text::make('IP', 'ip_address'),
            Text::make('Устройство', 'user_agent')
                ->changePreview(fn (?string $value, Text $field) => DeviceDetector::detect($value)),
            Text::make('User Agent', 'user_agent'),
            Date::make('Последняя активность', 'last_activity')
                ->format('d.m.Y H:i:s'),
        ];
    }
}
