<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Job;

use App\Models\Job\Job;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Job\Pages\JobIndexPage;
use App\MoonShine\Resources\Job\Pages\JobDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Job, JobIndexPage, JobDetailPage>
 */
class JobResource extends BaseResource
{
    protected string $model = Job::class;

    protected string $title = 'Журнал очереди';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            JobIndexPage::class,
            JobDetailPage::class,
        ];
    }
}
