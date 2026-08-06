<?php

declare(strict_types=1);

namespace App\Application\Broadcasts\Services;

use App\Application\Broadcasts\ProgressTracker;
use App\Application\Flows\Services\FlowEngine;
use App\Application\Services\LogService;
use App\Domain\Broadcasts\Enums\BroadcastRecipientStatus;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Exceptions\RateLimitException;
use App\Domain\Broadcasts\Models\Broadcast;
use App\Domain\Broadcasts\Models\BroadcastRecipient;
use App\Domain\Conversations\Enums\SubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;

/**
 * Оркестратор отправки broadcast-сообщения.
 * Не знает про Queue — чистый Application Service.
 */
final class BroadcastDispatcher
{
    public function __construct(
        private readonly FlowEngine $flowEngine,
        private readonly ProgressTracker $progressTracker,
    ) {}

    public function dispatch(int $broadcastId, int $subscriberId): void
    {
        $broadcast = Broadcast::find($broadcastId);

        if (!$broadcast || $broadcast->status === BroadcastStatus::CANCELLED) {
            return;
        }

        $recipient = BroadcastRecipient::where('broadcast_id', $broadcastId)
            ->where('bot_subscriber_id', $subscriberId)
            ->first();

        if (!$recipient || $recipient->status !== BroadcastRecipientStatus::PENDING) {
            return;
        }

        $subscriber = BotSubscriber::with('bot')->find($subscriberId);

        if (!$subscriber || $subscriber->status !== SubscriberStatus::ACTIVE) {
            $this->markFailed($recipient, 'Пользователь неактивен или не найден');
            return;
        }

        $version = $broadcast->flowVersion;

        if (!$version) {
            $this->markFailed($recipient, 'Версия потока не найдена');
            return;
        }

        try {
            $this->flowEngine->start($subscriber->bot, $subscriber, $version);
            $this->markSent($recipient);
        } catch (RequestException $e) {
            $this->handleRequestException($e, $recipient);
        } catch (\Exception $e) {
            LogService::logError('Broadcast send failed', [
                'broadcast_id' => $broadcastId,
                'subscriber_id' => $subscriberId,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($recipient, $e->getMessage());
        }
    }

    private function markSent(BroadcastRecipient $recipient): void
    {
        DB::transaction(function () use ($recipient) {
            $recipient->update([
                'status' => BroadcastRecipientStatus::SENT,
                'sent_at' => now(),
                'attempts' => DB::raw('attempts + 1'),
            ]);
        });

        $this->progressTracker->markSent($recipient->broadcast_id);
    }

    private function markFailed(BroadcastRecipient $recipient, string $error): void
    {
        DB::transaction(function () use ($recipient, $error) {
            $recipient->update([
                'status' => BroadcastRecipientStatus::FAILED,
                'error' => substr($error, 0, 255),
                'attempts' => DB::raw('attempts + 1'),
            ]);
        });

        $this->progressTracker->markFailed($recipient->broadcast_id);
    }

    private function handleRequestException(RequestException $e, BroadcastRecipient $recipient): void
    {
        $response = $e->response;

        if ($response->status() === 429) {
            $retryAfter = $response->json('parameters.retry_after', 60);

            LogService::logWarning('Broadcast 429', [
                'broadcast_id' => $recipient->broadcast_id,
                'retry_after' => $retryAfter,
            ]);

            throw new RateLimitException($retryAfter);
        }

        $this->markFailed($recipient, $e->getMessage());
    }
}
