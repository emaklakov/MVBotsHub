<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Job;

use App\Models\Job\Job;
use App\MoonShine\Resources\Concerns\HasPerPageSession;
use Illuminate\Database\Eloquent\Model;
use App\MoonShine\Resources\Job\Pages\JobIndexPage;
use App\MoonShine\Resources\Job\Pages\JobDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Job, JobIndexPage, JobDetailPage>
 */
class JobResource extends ModelResource
{
    use HasPerPageSession;

    protected string $model = Job::class;

    protected string $title = 'Журнал очереди';

    protected bool $withPolicy = true;

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

    public function canUpdate(): bool
    {
        return false;
    }

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
