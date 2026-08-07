<?php

declare(strict_types=1);

namespace App\Application\Broadcasts\Services;

use App\Application\Broadcasts\ProgressTracker;
use App\Application\Flows\Services\FlowEngine;
use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use App\Domain\Broadcasts\Enums\BroadcastRecipientStatus;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Exceptions\RateLimitException;
use App\Domain\Broadcasts\Models\Broadcast;
use App\Domain\Broadcasts\Models\BroadcastRecipient;
use App\Domain\Conversations\Enums\BotSubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Jobs\Telegram\SendBroadcastMessage;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;

final class BroadcastDispatcher
{
    public function __construct(
        private readonly FlowEngine $flowEngine,
        private readonly ProgressTracker $progressTracker,
    ) {}

    /**
     * Запустить рассылку для ВСЕХ pending-получателей.
     * Вызывается из админки (BroadcastDetailPage) либо из
     * DispatchScheduledBroadcasts (см. Console/Commands/Broadcasts).
     *
     * lazyById вместо pluck() — при аудитории в десятки/сотни тысяч
     * pluck() создал бы коллекцию всех id целиком в памяти вызывающего
     * процесса (обычно HTTP-запрос из админки, у него есть таймаут).
     */
    public function dispatchAll(Broadcast $broadcast): void
    {
        if ($broadcast->status === BroadcastStatus::CANCELLED) {
            return;
        }

        $broadcast->update(['status' => BroadcastStatus::PROCESSING, 'started_at' => now()]);

        BroadcastRecipient::query()
            ->where('broadcast_id', $broadcast->id)
            ->where('status', BroadcastRecipientStatus::PENDING)
            ->select('id', 'bot_subscriber_id')
            ->lazyById(500, column: 'id')
            ->each(fn (BroadcastRecipient $recipient) =>
            SendBroadcastMessage::dispatch($broadcast->id, $recipient->bot_subscriber_id, $broadcast->bot_id)
                ->onQueue('broadcasts')
            );
    }

    /**
     * Отправить одному получателю.
     * Вызывается из SendBroadcastMessage Job.
     */
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

        if (!$subscriber || $subscriber->status !== BotSubscriberStatus::ACTIVE) {
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

    /**
     * Повторить отправку для failed-получателей.
     * Заблокировавших бота подписчиков исключаем — им ретраить бессмысленно,
     * их BroadcastRecipient остаётся в failed навсегда.
     */
    public function retryFailed(Broadcast $broadcast): void
    {
        BroadcastRecipient::where('broadcast_id', $broadcast->id)
            ->where('status', BroadcastRecipientStatus::FAILED)
            ->whereHas('subscriber', fn ($q) => $q->where('status', BotSubscriberStatus::ACTIVE))
            ->update(['status' => BroadcastRecipientStatus::PENDING]);

        $this->dispatchAll($broadcast);
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

    /**
     * Разделяет постоянные ошибки Telegram (ретраить бессмысленно — чат
     * никогда не примет сообщение) от временных (сетевые сбои, 5xx —
     * должны штатно ретраиться джобой). 429 обрабатывается отдельно
     * и выше по стеку не долетает как RequestException.
     */
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

        if ($this->isPermanentDeliveryFailure($response)) {
            $this->blockSubscriber($recipient);
            $this->markFailed($recipient, $this->describePermanentFailure($response));
            return;
        }

        // Временная ошибка (5xx, неизвестный 4xx) — пусть Job ретраит штатно
        // (SendBroadcastMessage: tries=3, backoff=[5,15,60]).
        $this->markFailed($recipient, $e->getMessage());
        throw $e;
    }

    /**
     * 403 — пользователь заблокировал бота. 400 "chat not found" —
     * аккаунт удалён/чат недоступен. В обоих случаях ретраить бессмысленно:
     * чат никогда не станет снова доступен сам по себе.
     */
    private function isPermanentDeliveryFailure(Response $response): bool
    {
        if ($response->status() === 403) {
            return true;
        }

        return $response->status() === 400
            && str_contains((string) $response->json('description', ''), 'chat not found');
    }

    private function describePermanentFailure(Response $response): string
    {
        return $response->status() === 403
            ? 'Пользователь заблокировал бота'
            : 'Чат не найден (аккаунт удалён)';
    }

    private function blockSubscriber(BroadcastRecipient $recipient): void
    {
        $subscriber = $recipient->subscriber;

        if ($subscriber && $subscriber->status !== BotSubscriberStatus::BLOCKED) {
            $subscriber->update(['status' => BotSubscriberStatus::BLOCKED]);
        }
    }
}
