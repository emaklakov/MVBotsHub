<?php

namespace App\Jobs\Telegram;

use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendContactRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120];

    public function __construct(
        public Bot $bot,
        public int|string $chatId,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('telegram')];
    }

    public function handle(): void
    {
        try {
            $response = Telegraph::bot($this->bot->token)
                ->chat((string) $this->chatId)
                ->replyKeyboard(
                    ReplyKeyboard::make()->buttons([
                        ReplyButton::make('📱 Поделиться контактом')->requestContact(),
                    ])->resize()->oneTime()
                )
                ->message('Для продолжения работы, пожалуйста, поделитесь вашим номером телефона.')
                ->send();

            if (!$response->telegraphOk()) {
                LogService::logError('Не удалось запросить контакт', [
                    'bot_id' => $this->bot->id,
                    'chat_id' => $this->chatId,
                    'json' => $response->json(),
                ]);
            }
        } catch (RequestException $e) {
            $response = $e->response;

            if ($response->status() === 429) {
                $this->release($response->json('parameters.retry_after', 60));
                return;
            }

            LogService::logError('Telegram API error (SendContactRequest)', [
                'bot_id' => $this->bot->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            LogService::logError('Send contact request failed', [
                'bot_id' => $this->bot->id,
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
