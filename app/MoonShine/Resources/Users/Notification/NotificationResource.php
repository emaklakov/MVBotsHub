<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\Notification;

use App\Models\Users\Notification;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Users\Notification\Pages\NotificationDetailPage;
use App\MoonShine\Resources\Users\Notification\Pages\NotificationIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

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
