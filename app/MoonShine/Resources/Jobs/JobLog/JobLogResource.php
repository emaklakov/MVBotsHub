<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Jobs\JobLog;

use App\Models\Jobs\JobLog;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Jobs\JobLog\Pages\JobLogDetailPage;
use App\MoonShine\Resources\Jobs\JobLog\Pages\JobLogIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

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
