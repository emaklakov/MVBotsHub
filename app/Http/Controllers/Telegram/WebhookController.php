<?php

namespace App\Http\Controllers\Telegram;

use App\Domain\Bots\Enums\BotStatus;
use App\Domain\Bots\Models\Bot;
use App\Jobs\Telegram\ProcessTelegramUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController
{
    public function handle(Request $request, Bot $bot): JsonResponse
    {
        if($bot->status != BotStatus::ACTIVE) {
            return response()->json(['ok' => false], 404);
        }

        // 1. Проверяем секретный токен (сравнение, устойчивое к timing-атакам)
        $secret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');
        if (!$bot->webhook_secret_token || !hash_equals((string) $bot->webhook_secret_token, $secret)) {
            return response()->json(['ok' => false], 403);
        }

        // 2. Валидируем JSON
        $update = $request->all();
        if (empty($update)) {
            return response()->json(['ok' => false], 400);
        }

        // Диспатчим job и немедленно возвращаем 200 — Telegram не ждёт
        ProcessTelegramUpdate::dispatch($bot, $update)->onQueue('telegram');

        // 4. Мгновенно возвращаем 200 — никакой бизнес-логики синхронно
        return response()->json(['ok' => true]);
    }
}
