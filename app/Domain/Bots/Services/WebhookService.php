<?php

namespace App\Domain\Bots\Services;

use App\Domain\Bots\Models\Bot;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookService
{
    public function register(Bot $bot): bool
    {
        try {
            $secretToken = Str::random(32); // секретный токен, отправляемый в X-Telegram-Bot-Api-Secret-Tokenзаголовке для проверки подлинности веб-хука
            $webhookToken = Str::random(32); // публичный ID для URL

            $webhookUrl = route('telegram.webhook', ['bot' => $webhookToken]);
            $dropPendingUpdates = false; // удаляет ожидающие обновления из Telegram
            $maxConnections = 40; // Максимально допустимое количество одновременных подключений к веб-перехватчику (по умолчанию 40).

            $response = Http::post("https://api.telegram.org/bot{$bot->token}/setWebhook", [
                'url' => $webhookUrl,
                'secret_token' => $secretToken,
                'drop_pending_updates' => $dropPendingUpdates,
                'max_connections' => $maxConnections,
            ]);

            if ($response->json('ok') === true) {
                $bot->update([
                    'webhook_token' => $webhookToken,
                    'webhook_url' => $webhookUrl,
                    'webhook_secret_token' => $secretToken,
                ]);
                return true;
            } else {
                Log::error('Ошибка - App\Domain\Bots\Services\WebhookService::register', [
                    'status' => strval($response->status()),
                    'json' => $response->json(),
                ]);
            }

            return false;
        } catch (\Exception $exception) {
            Log::error('Ошибка - App\Domain\Bots\Services\WebhookService::register', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function unregister(Bot $bot): bool
    {
        try {
            $dropPendingUpdates = false; // удаляет ожидающие обновления из Telegram

            $response = Telegraph::bot($bot->token)
                ->unregisterWebhook($dropPendingUpdates)
                ->send();

            if ($response->telegraphOk()) {
                $bot->update([
                    'webhook_token' => null,
                    'webhook_url' => null,
                    'webhook_secret_token' => null,
                ]);
                return true;
            } else {
                Log::error('Ошибка - App\Domain\Bots\Services\WebhookService::register', [
                    'status' => strval($response->status()),
                    'json' => $response->json(),
                ]);
            }

            return false;
        } catch (\Exception $exception) {
            Log::error('Ошибка - App\Domain\Bots\Services\WebhookService::register', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }
    }
}
