<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Notification;

use App\Models\Admin\Notification;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Notification\Pages\NotificationIndexPage;
use App\MoonShine\Resources\Notification\Pages\NotificationDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Notification, NotificationIndexPage, NotificationDetailPage>
 */
class NotificationResource extends BaseResource
{
    protected string $model = Notification::class;

    protected string $title = 'Уведомления';

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
