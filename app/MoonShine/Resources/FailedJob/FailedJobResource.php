<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\FailedJob;

use App\Models\Job\FailedJob;
use App\MoonShine\Resources\Concerns\HasPerPageSession;
use App\MoonShine\Resources\FailedJob\Pages\FailedJobIndexPage;
use App\MoonShine\Resources\FailedJob\Pages\FailedJobDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FailedJobMassRetryController;

/**
 * @extends ModelResource<FailedJob, FailedJobIndexPage, FailedJobDetailPage>
 */
class FailedJobResource extends ModelResource
{
    use HasPerPageSession;

    protected string $model = FailedJob::class;

    protected string $title = 'Задачи с ошибками';

    protected bool $withPolicy = true;

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
            FailedJobIndexPage::class,
            FailedJobDetailPage::class,
        ];
    }
}
