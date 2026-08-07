<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\BotMessageTemplate;

use App\Domain\Bots\Models\BotMessageTemplate;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Bots\BotMessageTemplate\Pages\BotMessageTemplateFormPage;
use App\MoonShine\Resources\Telegram\Bots\BotMessageTemplate\Pages\BotMessageTemplateIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * Строки для каждого бота автоматически создаются BotObserver при создании
 * бота (см. App\Domain\Bots\Observers\BotObserver) — по одной на каждый
 * SystemMessageKey. Здесь их можно только редактировать, не создавать/удалять,
 * чтобы не появлялись "осиротевшие" ключи, которых нет в SystemMessageKey,
 * и чтобы не пропадали слоты, которые вернутся к fallback() из enum, но
 * молча, без индикации в списке.
 *
 * @extends ModelResource<BotMessageTemplate, BotMessageTemplateIndexPage, BotMessageTemplateFormPage>
 */
class BotMessageTemplateResource extends BaseResource
{
    protected string $model = BotMessageTemplate::class;

    protected string $title = 'Системные сообщения ботов';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(
            Action::CREATE,
            Action::DELETE,
            Action::MASS_DELETE,
        );
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->whereHas('bot', function (Builder $query) {
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
            BotMessageTemplateIndexPage::class,
            BotMessageTemplateFormPage::class,
        ];
    }
}
