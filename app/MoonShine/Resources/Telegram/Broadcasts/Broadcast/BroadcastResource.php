<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Broadcasts\Broadcast;

use App\Domain\Broadcasts\Models\Broadcast;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\Pages\BroadcastDetailPage;
use App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\Pages\BroadcastFormPage;
use App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\Pages\BroadcastIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\PageType;

/**
 * @extends ModelResource<Broadcast, BroadcastIndexPage, BroadcastFormPage, BroadcastDetailPage>
 */
class BroadcastResource extends BaseResource
{
    protected ?PageType $redirectAfterSave = PageType::DETAIL;

    protected string $model = Broadcast::class;

    protected string $title = 'Рассылки';

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
            BroadcastIndexPage::class,
            BroadcastFormPage::class,
            BroadcastDetailPage::class,
        ];
    }
}
