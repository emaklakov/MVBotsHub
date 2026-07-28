<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Notification;

use App\Models\Admin\Notification;
use App\MoonShine\Resources\Concerns\HasPerPageSession;
use App\MoonShine\Resources\Notification\Pages\NotificationIndexPage;
use App\MoonShine\Resources\Notification\Pages\NotificationDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Notification, NotificationIndexPage, NotificationDetailPage>
 */
class NotificationResource extends ModelResource
{
    use HasPerPageSession;

    protected bool $withPolicy = true;

    protected string $model = Notification::class;

    protected string $title = 'Уведомления';

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
            NotificationIndexPage::class,
            NotificationDetailPage::class,
        ];
    }
}
