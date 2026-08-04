<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient\Pages;

use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient\BroadcastRecipientResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;


/**
 * @extends FormPage<BroadcastRecipientResource>
 */
class BroadcastRecipientFormPage extends BaseFormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
            ]),
        ];
    }
}
