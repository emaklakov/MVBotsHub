<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Session;

use App\Models\Admin\User\Session;
use App\MoonShine\Resources\Concerns\HasPerPageSession;
use App\MoonShine\Resources\Session\Pages\SessionDetailPage;
use App\MoonShine\Resources\Session\Pages\SessionIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * Класс SessionResource представляет ресурс для работы с пользовательскими сессиями.
 * Определяет заголовок, компоненты и другие параметры ресурса.
 */
class SessionResource extends ModelResource
{
    use HasPerPageSession;

    protected bool $withPolicy = true;

    protected string $model = Session::class;

    protected string $title = 'Сессии';

    public function perPageValues(): array
    {
        return [
            6 => 6,
            12 => 12,
            26 => 26,
        ];
    }

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
