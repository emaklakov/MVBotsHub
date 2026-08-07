<?php

declare(strict_types=1);

namespace App\Domain\Flows\Contracts;

use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Models\Bot;

interface MessageSenderInterface
{
    /**
     * Ставит сообщение в очередь на отправку. Не отправляет сразу —
     * накапливает до вызова flush(), чтобы все сообщения одного
     * прогона FlowEngine ушли строго в порядке постановки (см. flush()).
     */
    public function send(SendMessage $sendMessage): void;

    public function requestContact(Bot $bot, int $telegramId): void;

    /**
     * Диспатчит всё накопленное с send()/requestContact() одной цепочкой
     * (Bus::chain), гарантируя порядок доставки в рамках одного вызова.
     * Обязательно вызывается ровно один раз в конце прогона FlowEngine.
     */
    public function flush(): void;
}
