<?php

namespace App\Jobs\Telegram;

use App\Application\Services\LogService;
use App\Domain\Broadcasts\Enums\BroadcastRecipientStatus;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Models\Broadcast;
use App\Domain\Broadcasts\Models\BroadcastRecipient;
use App\Domain\Conversations\Enums\SubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Flows\Services\FlowRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SendBroadcastMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $broadcastId,
        public int $subscriberId,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('telegram')];
    }

    public function handle(): void
    {
        $broadcast = Broadcast::find($this->broadcastId);

        if (!$broadcast || $broadcast->status === BroadcastStatus::CANCELLED) {
            return;
        }

        $recipient = BroadcastRecipient::where('broadcast_id', $this->broadcastId)
            ->where('bot_subscriber_id', $this->subscriberId)
            ->first();

        if (!$recipient || $recipient->status !== BroadcastRecipientStatus::PENDING) {
            return;
        }

        $subscriber = BotSubscriber::with('bot')->find($this->subscriberId);

        if (!$subscriber || $subscriber->status !== SubscriberStatus::ACTIVE) {
            $this->markFailed($recipient, 'Пользователь неактивен или не найден');
            return;
        }

        try {
            $version = $broadcast->flowVersion;

            if (!$version) {
                $this->markFailed($recipient, 'Версия потока не найдена');
                return;
            }

            $runner = new FlowRunner($subscriber->bot, $subscriber, $version);
            $runner->start();

            $this->markSent($recipient);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $response = $e->response;

            if ($response->status() === 429) {
                $retryAfter = $response->json('parameters.retry_after', 60);
                LogService::logWarning('Broadcast 429', [
                    'broadcast_id' => $this->broadcastId,
                    'retry_after' => $retryAfter,
                ]);
                $this->release($retryAfter);
                return;
            }

            $this->markFailed($recipient, $e->getMessage());
        } catch (\Exception $e) {
            LogService::logError('Broadcast send failed', [
                'broadcast_id' => $this->broadcastId,
                'subscriber_id' => $this->subscriberId,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($recipient, $e->getMessage());
        }
    }

    protected function markSent(BroadcastRecipient $recipient): void
    {
        $recipient->update([
            'status' => BroadcastRecipientStatus::SENT,
            'sent_at' => now(),
            'attempts' => DB::raw('attempts + 1'),
        ]);

        Redis::incr("broadcast:{$this->broadcastId}:sent");
        $this->maybeFlushProgress();
    }

    protected function markFailed(BroadcastRecipient $recipient, string $error): void
    {
        $recipient->update([
            'status' => BroadcastRecipientStatus::FAILED,
            'error' => substr($error, 0, 255),
            'attempts' => DB::raw('attempts + 1'),
        ]);

        Redis::incr("broadcast:{$this->broadcastId}:failed");
        $this->maybeFlushProgress();
    }

    protected function maybeFlushProgress(): void
    {
        $sent = (int) Redis::get("broadcast:{$this->broadcastId}:sent");
        $failed = (int) Redis::get("broadcast:{$this->broadcastId}:failed");

        // Flush каждые 50 сообщений или просто всегда (атомарно через DB)
        if (($sent + $failed) % 50 === 0) {
            Broadcast::where('id', $this->broadcastId)->update([
                'sent_count' => $sent,
                'failed_count' => $failed,
            ]);
        }

        // Проверяем завершение
        $broadcast = Broadcast::find($this->broadcastId);
        if ($broadcast && ($sent + $failed) >= $broadcast->total_recipients && $broadcast->status === BroadcastStatus::PROCESSING) {
            $broadcast->update([
                'status' => BroadcastStatus::COMPLETED,
                'sent_count' => $sent,
                'failed_count' => $failed,
                'completed_at' => now(),
            ]);
            Redis::del("broadcast:{$this->broadcastId}:sent");
            Redis::del("broadcast:{$this->broadcastId}:failed");
        }
    }
}
