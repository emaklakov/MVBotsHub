<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Queues\FailedJob;

use App\Domain\Queues\FailedJob;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Queues\FailedJob\Pages\FailedJobDetailPage;
use App\MoonShine\Resources\Queues\FailedJob\Pages\FailedJobIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<FailedJob, FailedJobIndexPage, FailedJobDetailPage>
 */
class FailedJobResource extends BaseResource
{
    protected string $model = FailedJob::class;

    protected string $title = 'Задачи с ошибками';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            FailedJobIndexPage::class,
            FailedJobDetailPage::class,
        ];
    }
}
