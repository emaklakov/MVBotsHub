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
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<FlowVersion, FlowVersionIndexPage, FlowVersionFormPage, FlowVersionDetailPage>
 */
class FlowVersionResource extends BaseResource
{
    protected string $model = FlowVersion::class;

    protected string $title = 'Версии потоков';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(
                Action::CREATE,
                Action::UPDATE
            );
    }

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

    protected function search(): array
    {
        return ['version_number'];
    }
}
