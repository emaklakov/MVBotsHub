<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Session;

use App\Models\Session;
use App\MoonShine\Resources\Session\Pages\SessionIndexPage;
use App\MoonShine\Resources\Session\Pages\SessionDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * Класс SessionResource представляет ресурс для работы с пользовательскими сессиями.
 * Определяет заголовок, компоненты и другие параметры ресурса.
 */
class SessionResource extends ModelResource
{
    protected bool $withPolicy = true;

    protected string $model = Session::class;

    protected string $title = 'Сессии';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            SessionIndexPage::class,
            SessionDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'ip_address',
            'user_agent',
            'user.email'
        ];
    }
}
