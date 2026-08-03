<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Conversations\ConversationSession\Pages;

use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Telegram\Conversations\ConversationSession\ConversationSessionResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\ID;


/**
 * @extends DetailPage<ConversationSessionResource>
 */
class ConversationSessionDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
        ];
    }
}
