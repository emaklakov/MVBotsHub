<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Flows\Flow;

use App\Domain\Flows\Models\Flow;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Flows\Flow\Pages\FlowDetailPage;
use App\MoonShine\Resources\Telegram\Flows\Flow\Pages\FlowFormPage;
use App\MoonShine\Resources\Telegram\Flows\Flow\Pages\FlowIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\PageType;

/**
 * @extends ModelResource<Flow, FlowIndexPage, FlowFormPage, FlowDetailPage>
 */
class FlowResource extends BaseResource
{
    protected ?PageType $redirectAfterSave = PageType::DETAIL;

    protected string $model = Flow::class;

    protected string $title = 'Потоки';

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->where(function (Builder $query) {
            $query->whereHas('bot', function (Builder $query) {
                $query->whereHas('members', function (Builder $q) {
                    $q->where('user_id', auth()->id());
                });
            });
        });
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            FlowIndexPage::class,
            FlowFormPage::class,
            FlowDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return ['id', 'name', 'trigger_value'];
    }
}
