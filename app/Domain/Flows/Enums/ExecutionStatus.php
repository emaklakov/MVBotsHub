<?php

declare(strict_types=1);

namespace App\Domain\Flows\Enums;

enum ExecutionStatus
{
    case CONTINUE;
    case WAITING;
}
