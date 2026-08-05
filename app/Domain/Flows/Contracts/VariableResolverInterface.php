<?php

declare(strict_types=1);

namespace App\Domain\Flows\Contracts;

use App\Domain\Conversations\Models\BotSubscriber;

interface VariableResolverInterface
{
    public function resolve(string $template, array $context, BotSubscriber $subscriber): string;
}
