<?php

namespace App\Domain\Bots\Services;

use App\Domain\Bots\Models\Bot;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Str;

class WebhookRegistrar
{
    public function register(Bot $bot): bool
    {
        try {
            $secret = Str::random(32);
            $webhookToken = Str::random(32);

            $webhookUrl = route('telegram.webhook', ['bot' => $webhookToken]);

            $response = Telegraph::chat('')
                ->token($bot->token)
                ->registerWebhook($webhookUrl, $secret)
                ->send();

            if ($response->telegraphOk()) {
                $bot->update([
                    'webhook_token' => $webhookToken,
                    'webhook_url' => $webhookUrl,
                    'webhook_secret_token' => $secret,
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
}
