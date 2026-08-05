<?php

declare(strict_types=1);

namespace App\Domain\Flows\DTO;

use App\Domain\Flows\Enums\ExecutionStatus;

final readonly class BlockExecutionResult
{
    public function __construct(
        public ?string $nextBlockId = null,
        public ExecutionStatus $status = ExecutionStatus::CONTINUE,
    ) {}
}
