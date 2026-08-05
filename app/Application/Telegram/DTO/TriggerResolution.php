<?php

declare(strict_types=1);

namespace App\Application\Telegram\DTO;

use App\Domain\Flows\Models\FlowVersion;

final readonly class TriggerResolution
{
    public function __construct(
        public FlowVersion $flowVersion,
        public array $parameters = [],
    ) {}
}
