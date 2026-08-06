<?php

namespace App\Application\Bots\Services;

use App\Application\Services\LogService;
use App\Domain\Bots\Models\Bot;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookService
{
    /** Максимально допустимое количество одновременных подключений к веб-хуку (по умолчанию 40). */
    private const MAX_CONNECTIONS = 40;

    /**
     * Регистрация вебхука на СВОЁМ URL (route('telegram.webhook', ...)).
     * Telegraph::registerWebhook() тут не подходит: он всегда строит URL
     * через собственный роут пакета 'telegraph.webhook' (/telegraph/{token}/webhook),
     * который в проекте не используется — поэтому вызываем Bot API напрямую.
     */
    public function register(Bot $bot): bool
    {
        try {
            $secretToken = Str::random(32); // секретный токен, отправляемый в X-Telegram-Bot-Api-Secret-Tokenзаголовке для проверки подлинности веб-хука
            $webhookToken = Str::random(32); // публичный ID для URL

            $webhookUrl = route('telegram.webhook', ['bot' => $webhookToken]);

            $response = Http::post("https://api.telegram.org/bot{$bot->token}/setWebhook", [
                'url' => $webhookUrl,
                'secret_token' => $secretToken,
                'drop_pending_updates' => false, // удаляет ожидающие обновления из Telegram
                'max_connections' => config('services.telegram.max_connections', self::MAX_CONNECTIONS),
            ]);

            if ($response->json('ok') !== true) {
                LogService::logError('status: '.strval($response->status()), $response->json());
                return false;
            }

            $bot->update([
                'webhook_token' => $webhookToken,
                'webhook_url' => $webhookUrl,
                'webhook_secret_token' => $secretToken,
            ]);

            return true;
        } catch (\Throwable $exception) {
            LogService::logError($exception->getMessage(), $exception->getTraceAsString());
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

            if (!$response->telegraphOk()) {
                LogService::logError('status: '.strval($response->status()), $response->json());
                return false;
            }

            $bot->update([
                'webhook_token' => null,
                'webhook_url' => null,
                'webhook_secret_token' => null,
            ]);

            return true;
        } catch (\Throwable $exception) {
            LogService::logError($exception->getMessage(), $exception->getTraceAsString());
            return false;
        }
    }
}
