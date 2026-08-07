<?php

declare(strict_types=1);

namespace App\Domain\Flows\Enums;

enum BlockType: string
{
    // Bubbles
    case TEXT        = 'text';
    case IMAGE       = 'image';
    case VIDEO       = 'video';
    case AUDIO       = 'audio';
    case FILE        = 'file';
    case POLL        = 'poll';
    // Inputs
    case INPUT       = 'input';
    case BUTTON      = 'button';
    case NUMBER      = 'number';
    case EMAIL       = 'email';
    case PHONE       = 'phone';
    case DATE        = 'date';
    case GEOLOCATION = 'geolocation';
    case CONTACT     = 'contact';
    // Logic
    case CONDITION   = 'condition';
    // Other
    case JUMP = 'jump';
    case API_CALL = 'api_call';
    case DELAY = 'delay';
}
