<?php

namespace App\Http\Controllers\Telegram;

use App\Domain\Bots\Models\Bot;
use App\Jobs\Telegram\ProcessTelegramUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController
{
    public function handle(Request $request, Bot $bot): JsonResponse
    {
        // 1. Проверяем секретный токен
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($secret !== $bot->webhook_secret_token) {
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
