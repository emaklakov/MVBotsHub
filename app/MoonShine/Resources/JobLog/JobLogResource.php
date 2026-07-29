<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\JobLog;

use App\Models\Job\JobLog;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\JobLog\Pages\JobLogIndexPage;
use App\MoonShine\Resources\JobLog\Pages\JobLogDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<JobLog, JobLogIndexPage, JobLogDetailPage>
 */
class JobLogResource extends BaseResource
{
    protected string $model = JobLog::class;

    protected string $title = 'Логи очереди';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            JobLogIndexPage::class,
            JobLogDetailPage::class,
        ];
    }
}
