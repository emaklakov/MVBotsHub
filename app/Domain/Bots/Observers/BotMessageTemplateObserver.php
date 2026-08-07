<?php

declare(strict_types=1);

namespace App\Domain\Bots\Observers;

use App\Application\Bots\Services\SystemMessageResolver;
use App\Domain\Bots\Models\BotMessageTemplate;

final class BotMessageTemplateObserver
{
    public function saved(BotMessageTemplate $template): void
    {
        SystemMessageResolver::forgetCache($template->bot_id, $template->key);
    }

    public function deleted(BotMessageTemplate $template): void
    {
        SystemMessageResolver::forgetCache($template->bot_id, $template->key);
    }
}
