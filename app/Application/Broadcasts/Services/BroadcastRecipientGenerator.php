<?php

declare(strict_types=1);

namespace App\Application\Broadcasts\Services;

use App\Application\Audiences\Services\AudienceResolver;
use App\Domain\Broadcasts\Enums\BroadcastRecipientStatus;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Models\Broadcast;
use App\Domain\Broadcasts\Models\BroadcastRecipient;
use App\Domain\Conversations\Enums\BotSubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;

final class BroadcastRecipientGenerator
{
    public function __construct(
        private readonly AudienceResolver $audienceResolver,
    ) {}

    /**
     * Создаёт получателей: по Audience рассылки (static/dynamic сегмент),
     * либо, если audience_id = null, по всем активным подписчикам бота
     * (обратная совместимость со старыми рассылками). Пропускает дубли.
     *
     * chunkById вместо pluck()->foreach — при аудитории в десятки/сотни
     * тысяч подписчиков pluck() загрузил бы все id разом в память процесса,
     * который это вызывает (обычно HTTP-запрос из админки).
     */
    public function generate(Broadcast $broadcast): int
    {
        $existingSubscriberIds = BroadcastRecipient::query()
            ->where('broadcast_id', $broadcast->id)
            ->pluck('bot_subscriber_id')
            ->toArray();

        $query = $broadcast->audience
            ? $this->audienceResolver->query($broadcast->audience)
            : BotSubscriber::query()->where('bot_id', $broadcast->bot_id)->where('status', BotSubscriberStatus::ACTIVE);

        $now = now();
        $created = 0;

        $query->select('bot_subscribers.id')
            ->when(!empty($existingSubscriberIds), fn ($q) => $q->whereNotIn('bot_subscribers.id', $existingSubscriberIds))
            ->chunkById(1000, function ($subscribers) use ($broadcast, $now, &$created) {
                $records = $subscribers->map(fn (BotSubscriber $s) => [
                    'broadcast_id' => $broadcast->id,
                    'bot_subscriber_id' => $s->id,
                    'status' => BroadcastRecipientStatus::PENDING,
                    'attempts' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray();

                BroadcastRecipient::insert($records);
                $created += count($records);
            }, column: 'id');

        $broadcast->update([
            'total_recipients' => BroadcastRecipient::where('broadcast_id', $broadcast->id)->count(),
        ]);

        return $created;
    }

    /**
     * Добавить одного подписчика во все pending-рассылки бота.
     * Вызывать при подписке нового пользователя.
     *
     * Для рассылок с dynamic-сегментом это НЕ нужно — сегмент пересчитывается
     * заново при следующем generate() и подхватит подписчика сам, если тот
     * подходит под условия фильтра. Здесь добавляем только туда, где
     * audience_id = null (все активные) или audience static и подписчик
     * уже явно включён в список.
     */
    public function addSubscriberToPendingBroadcasts(BotSubscriber $subscriber): void
    {
        $pendingBroadcasts = Broadcast::query()
            ->where('bot_id', $subscriber->bot_id)
            ->where('status', BroadcastStatus::PENDING)
            ->whereNull('audience_id')
            ->pluck('id');

        $now = now();
        $records = $pendingBroadcasts->map(fn (int $id) => [
            'broadcast_id' => $id,
            'bot_subscriber_id' => $subscriber->id,
            'status' => BroadcastRecipientStatus::PENDING,
            'attempts' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        if (!empty($records)) {
            BroadcastRecipient::insert($records);
        }
    }
}
