<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\ConversationSession;

use App\Domain\Conversations\Models\ConversationSession;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Conversations\ConversationSession\Pages\ConversationSessionDetailPage;
use App\MoonShine\Resources\Telegram\Conversations\ConversationSession\Pages\ConversationSessionIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<ConversationSession, ConversationSessionIndexPage, ConversationSessionDetailPage>
 */
class ConversationSessionResource extends BaseResource
{
    protected string $model = ConversationSession::class;

    protected string $title = 'Сессии диалогов';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            ConversationSessionIndexPage::class,
            ConversationSessionDetailPage::class,
        ];
    }
}
