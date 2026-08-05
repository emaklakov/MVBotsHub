<?php

declare(strict_types=1);

namespace App\Domain\Flows\Entities;

use Carbon\Carbon;

final class FlowSession
{
    public function __construct(
        public readonly int $id,
        public readonly int $botSubscriberId,
        public readonly int $flowVersionId,
        public string $currentGroupId,
        public string $currentBlockId,
        public array $context,
        public string $status,
        public readonly ?Carbon $expiresAt = null,
    ) {}
}
