<?php

declare(strict_types=1);

namespace App\Application\Audiences\Services;

use App\Domain\Audiences\Enums\AudienceType;
use App\Domain\Audiences\Models\Audience;
use App\Domain\Conversations\Enums\BotSubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use Illuminate\Database\Eloquent\Builder;

/**
 * Превращает Audience (static или dynamic) в Eloquent-запрос по BotSubscriber.
 * Единая точка входа — и для генерации получателей рассылки
 * (BroadcastRecipientGenerator), и для превью размера аудитории в админке.
 */
final class AudienceResolver
{
    public function query(Audience $audience): Builder
    {
        return match ($audience->type) {
            AudienceType::STATIC => $this->staticQuery($audience),
            AudienceType::DYNAMIC => $this->dynamicQuery($audience),
        };
    }

    public function count(Audience $audience): int
    {
        return $this->query($audience)->count();
    }

    public function refreshCachedCount(Audience $audience): void
    {
        $audience->update([
            'cached_count' => $this->count($audience),
            'cached_count_at' => now(),
        ]);
    }

    private function staticQuery(Audience $audience): Builder
    {
        return $audience->subscribers()->getQuery()
            ->where('status', BotSubscriberStatus::ACTIVE);
    }

    private function dynamicQuery(Audience $audience): Builder
    {
        $filters = $audience->filters ?? [];

        $query = BotSubscriber::query()
            ->where('bot_id', $audience->bot_id)
            ->where('status', BotSubscriberStatus::ACTIVE);

        if (!empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        // Активны за последние N дней
        if (!empty($filters['active_within_days'])) {
            $query->where('last_activity_at', '>=', now()->subDays((int) $filters['active_within_days']));
        }

        // Подписаны минимум N дней назад (отсекаем совсем свежих)
        if (!empty($filters['subscribed_days_ago'])) {
            $query->where('created_at', '<=', now()->subDays((int) $filters['subscribed_days_ago']));
        }

        // Произвольный тег в settings jsonb, например {"tag": "vip"}
        if (!empty($filters['settings_tag'])) {
            $query->where('settings->tag', $filters['settings_tag']);
        }

        return $query;
    }
}
