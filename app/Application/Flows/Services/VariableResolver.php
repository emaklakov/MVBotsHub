<?php

declare(strict_types=1);

namespace App\Application\Flows\Services;

use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Flows\Contracts\VariableResolverInterface;

final class VariableResolver implements VariableResolverInterface
{
    public function resolve(string $template, array $context, BotSubscriber $subscriber): string
    {
        $variables = $context;

        $variables['subscriber.telegram_id'] = $subscriber->telegram_id;
        $variables['subscriber.username'] = $subscriber->telegram_username ?? '';
        $variables['subscriber.language'] = $subscriber->effectiveLanguage;

        if ($subscriber->person) {
            $variables['person.phone'] = $subscriber->person->phone;
        }

        foreach ($variables as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace("{{{$key}}}", (string) $value, $template);
            }
        }

        return preg_replace('/\{\{[^}]+\}\}/', '', $template);
    }
}
