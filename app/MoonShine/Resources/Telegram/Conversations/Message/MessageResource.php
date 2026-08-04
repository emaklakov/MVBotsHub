<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\Message;

use App\Domain\Conversations\Models\Message;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Telegram\Conversations\Message\Pages\MessageFormPage;
use App\MoonShine\Resources\Telegram\Conversations\Message\Pages\MessageIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Message, MessageIndexPage, MessageFormPage>
 */
class MessageResource extends BaseResource
{
    protected string $model = Message::class;

    protected string $title = 'Сообщения';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            MessageIndexPage::class,
            MessageFormPage::class,
        ];
    }
}
