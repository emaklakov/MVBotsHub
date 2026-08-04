<?php

namespace App\MoonShine\Resources\Telegram\Conversations\Conversation\Pages;

use App\MoonShine\Resources\Base\BaseFormPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Support\Enums\PageType;
use Symfony\Component\HttpFoundation\Response;

class ConversationFormPage extends BaseFormPage
{
    protected function modifyResponse(): ?Response
    {
        $resource = $this->getResource();
        $id = $resource->getItemID();

        if ($id) {
            return redirect()->to($resource->getDetailPageUrl($id));
        }

        return null;
    }

    /**
     * @return FieldContract
     */
    protected function fields(): iterable
    {
        return [];
    }
}
