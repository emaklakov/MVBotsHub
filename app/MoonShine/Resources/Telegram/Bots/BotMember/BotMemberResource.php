<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\BotMember;

use App\Domain\Bots\Models\BotMember;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Bots\BotMember\Pages\BotMemberFormPage;
use App\MoonShine\Resources\Telegram\Bots\BotMember\Pages\BotMemberIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<BotMember, BotMemberIndexPage, BotMemberFormPage>
 */
class BotMemberResource extends BaseResource
{
    protected string $model = BotMember::class;

    protected string $title = 'Доступы к боту';

    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        request()->merge([
            'created_by' => auth()->id(),
        ]);

        return $item;
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            BotMemberIndexPage::class,
            BotMemberFormPage::class,
        ];
    }
}
