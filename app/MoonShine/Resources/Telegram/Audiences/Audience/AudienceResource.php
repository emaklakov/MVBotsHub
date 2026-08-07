<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Audiences\Audience;

use App\Domain\Audiences\Models\Audience;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Audiences\Audience\Pages\AudienceDetailPage;
use App\MoonShine\Resources\Telegram\Audiences\Audience\Pages\AudienceFormPage;
use App\MoonShine\Resources\Telegram\Audiences\Audience\Pages\AudienceIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Audience, AudienceIndexPage, AudienceFormPage, AudienceDetailPage>
 */
class AudienceResource extends BaseResource
{
    protected string $model = Audience::class;

    protected string $title = 'Списки рассылки';

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->whereHas('bot.members', fn (Builder $q) => $q->where('user_id', auth()->id()));
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            AudienceIndexPage::class,
            AudienceFormPage::class,
            AudienceDetailPage::class,
        ];
    }
}
