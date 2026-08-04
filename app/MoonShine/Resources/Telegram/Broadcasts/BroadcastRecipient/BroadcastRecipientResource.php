<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient;

use App\Domain\Broadcasts\Models\BroadcastRecipient;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient\Pages\BroadcastRecipientDetailPage;
use App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient\Pages\BroadcastRecipientFormPage;
use App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient\Pages\BroadcastRecipientIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<BroadcastRecipient, BroadcastRecipientIndexPage, BroadcastRecipientFormPage, BroadcastRecipientDetailPage>
 */
class BroadcastRecipientResource extends BaseResource
{
    protected string $model = BroadcastRecipient::class;

    protected string $title = 'Получатели рассылки';

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
            BroadcastRecipientIndexPage::class,
            BroadcastRecipientFormPage::class,
            BroadcastRecipientDetailPage::class,
        ];
    }
}
