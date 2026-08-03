<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Flows\FlowVersion;

use App\Domain\Flows\Models\FlowVersion;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Flows\FlowVersion\Pages\FlowVersionDetailPage;
use App\MoonShine\Resources\Telegram\Flows\FlowVersion\Pages\FlowVersionFormPage;
use App\MoonShine\Resources\Telegram\Flows\FlowVersion\Pages\FlowVersionIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<FlowVersion, FlowVersionIndexPage, FlowVersionFormPage, FlowVersionDetailPage>
 */
class FlowVersionResource extends BaseResource
{
    protected string $model = FlowVersion::class;

    protected string $title = 'Версии потоков';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            FlowVersionIndexPage::class,
            FlowVersionFormPage::class,
            FlowVersionDetailPage::class,
        ];
    }
}
