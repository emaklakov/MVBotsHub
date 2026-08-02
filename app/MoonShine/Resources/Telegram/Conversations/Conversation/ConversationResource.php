<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\Conversation;

use App\Domain\Conversations\Models\Conversation;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Conversations\Conversation\Pages\ConversationDetailPage;
use App\MoonShine\Resources\Telegram\Conversations\Conversation\Pages\ConversationIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Conversation, ConversationIndexPage, ConversationDetailPage>
 */
class ConversationResource extends BaseResource
{
    protected string $model = Conversation::class;

    protected string $title = 'Диалоги';

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->where(function (Builder $query) {
            $query->whereHas('subscriber.bot', function (Builder $query) {
                $query->whereHas('users', function (Builder $q) {
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
            ConversationIndexPage::class,
            ConversationDetailPage::class,
        ];
    }
}
