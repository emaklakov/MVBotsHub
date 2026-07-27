<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\JobLog;

use App\Models\Job\JobLog;
use App\MoonShine\Resources\Concerns\HasPerPageSession;
use App\MoonShine\Resources\JobLog\Pages\JobLogIndexPage;
use App\MoonShine\Resources\JobLog\Pages\JobLogDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<JobLog, JobLogIndexPage, JobLogDetailPage>
 */
class JobLogResource extends ModelResource
{
    use HasPerPageSession;

    protected bool $withPolicy = true;

    protected string $model = JobLog::class;

    protected string $title = 'Логи очереди';

    public function perPageValues(): array
    {
        return [
            6 => 6,
            12 => 12,
            26 => 26,
        ];
    }

    public function canCreate(): bool
    {
        return false;
    }

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
