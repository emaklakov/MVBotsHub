<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Jobs\Job;

use App\Models\Jobs\Job;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Jobs\Job\Pages\JobDetailPage;
use App\MoonShine\Resources\Jobs\Job\Pages\JobIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

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
