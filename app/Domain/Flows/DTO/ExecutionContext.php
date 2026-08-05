<?php

declare(strict_types=1);

namespace App\Domain\Flows\DTO;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Flows\Entities\FlowSession;
use App\Domain\Flows\Models\FlowVersion;

/**
 * Контекст одного шага выполнения. Передаётся во все Executors.
 */
final readonly class ExecutionContext
{
    public function __construct(
        public FlowSession $session,
        public BotSubscriber $subscriber,
        public Bot $bot,
        public FlowVersion $version,
    ) {}
}
