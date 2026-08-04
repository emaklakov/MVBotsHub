<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\BotSubscriber;

use App\Domain\Conversations\Models\BotSubscriber;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\Pages\BotSubscriberDetailPage;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\Pages\BotSubscriberFormPage;
use App\MoonShine\Resources\Telegram\Conversations\BotSubscriber\Pages\BotSubscriberIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<BotSubscriber, BotSubscriberIndexPage, BotSubscriberDetailPage, BotSubscriberFormPage>
 */
class BotSubscriberResource extends BaseResource
{
    protected string $model = BotSubscriber::class;

    protected string $title = 'Пользователи бота';

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
            BotSubscriberIndexPage::class,
            BotSubscriberDetailPage::class,
            BotSubscriberFormPage::class,
        ];
    }

    protected function search(): array
    {
        return ['telegram_id', 'telegram_username'];
    }
}
