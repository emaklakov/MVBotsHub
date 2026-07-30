<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\Session;

use App\Models\Admin\User\Session;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Users\Session\Pages\SessionDetailPage;
use App\MoonShine\Resources\Users\Session\Pages\SessionIndexPage;
use MoonShine\Contracts\Core\PageContract;

/**
 * Класс SessionResource представляет ресурс для работы с пользовательскими сессиями.
 * Определяет заголовок, компоненты и другие параметры ресурса.
 */
class SessionResource extends BaseResource
{
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
