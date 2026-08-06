<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Telegram\DTO\TriggerResolution;
use App\Domain\Bots\Models\Bot;
use App\Domain\Flows\Enums\FlowStatus;
use App\Domain\Flows\Enums\FlowVersionStatus;
use App\Domain\Flows\Enums\TriggerTypes;
use App\Domain\Flows\Models\Flow;

final class FlowTriggerResolver
{
    public function resolve(Bot $bot, string $textInput): ?TriggerResolution
    {
        $command = explode('@', ltrim($textInput, '/'))[0];
        $parts = explode(' ', $textInput, 2);
        $param   = $parts[1] ?? null;

        if ($param) {
            $flow = Flow::query()
                ->where('bot_id', $bot->id)
                ->where('trigger_type', TriggerTypes::DEEPLINK)
                ->where('trigger_value', $param)
                ->where('status', FlowStatus::ACTIVE)
                ->first();
        } else {
            $flow = Flow::query()
                ->where('bot_id', $bot->id)
                ->where('trigger_type', TriggerTypes::COMMAND)
                ->where('trigger_value', $command)
                ->where('status', FlowStatus::ACTIVE)
                ->first();
        }

        if (!$flow) {
            return null;
        }

        $version = $flow->versions()
            ->where('status', FlowVersionStatus::PUBLISHED)
            ->latest('published_at')
            ->first();

        if (!$version) {
            return null;
        }

        return new TriggerResolution(
            flowVersion: $version,
            parameters: $param ? ['deeplink' => $param] : []
        );
    }
}
