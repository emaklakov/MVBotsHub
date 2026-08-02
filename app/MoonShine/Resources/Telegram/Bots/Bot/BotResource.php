<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\Bot;

use App\Domain\Bots\Enums\BotMemberRole;
use App\Domain\Bots\Models\Bot;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Bots\Bot\Pages\BotDetailPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\Pages\BotFormPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\Pages\BotIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Bot, BotIndexPage, BotFormPage, BotDetailPage>
 */
class BotResource extends BaseResource
{
    protected string $model = Bot::class;

    protected string $title = 'Боты';

    protected array $with = ['members'];

    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
//        // подмешиваем owner_id в данные запроса до заполнения модели
//        request()->merge([
//            'owner_id' => auth()->id(),
//        ]);

        return $item;
    }

    protected function afterCreated(DataWrapperContract $item): DataWrapperContract
    {
        $item->members()->create([
            'user_id' => auth()->id(),
            'role' => BotMemberRole::OWNER,
        ]);

        return $item;
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->where(function (Builder $query) {
            $query->whereHas('members', function (Builder $q) {
                    $q->where('user_id', auth()->id());
            });
        });
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            BotIndexPage::class,
            BotFormPage::class,
            BotDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return ['id', 'name', 'username'];
    }
}
