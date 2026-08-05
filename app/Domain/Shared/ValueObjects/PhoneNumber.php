<?php

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

final readonly class PhoneNumber
{
    public function __construct(public string $value) {
        if (!preg_match('/^\+?[1-9]\d{1,14}$/', $value)) {
            throw new InvalidArgumentException('Invalid phone');
        }
    }
}
