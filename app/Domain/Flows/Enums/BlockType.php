<?php

declare(strict_types=1);

namespace App\Domain\Flows\Enums;

enum BlockType: string
{
    case TEXT = 'text';
    case BUTTON = 'button';
    case INPUT = 'input';
    case CONDITION = 'condition';
    case JUMP = 'jump';
    case API_CALL = 'api_call';
    case DELAY = 'delay';
}
