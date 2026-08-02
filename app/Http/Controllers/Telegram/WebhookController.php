<?php

namespace App\Http\Controllers\Telegram;

use App\Domain\Bots\Models\Bot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function handle(Request $request, Bot $bot): JsonResponse
    {
        // 1. Проверяем секретный токен
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($secret !== $bot->webhook_secret_token) {
            Log::warning('Invalid webhook secret', ['bot_id' => $bot->id]);
            return response()->json(['ok' => false], 403);
        }

        // 2. Валидируем JSON
        $update = $request->all();
        if (empty($update)) {
            return response()->json(['ok' => false], 400);
        }

        // 3. Сохраняем сырой апдейт (опционально, для аудита)
        // TODO: Итерация 1 — диспатчить ProcessTelegramUpdate job

        Log::debug(json_encode($update));

        // 4. Мгновенно возвращаем 200 — никакой бизнес-логики синхронно
        return response()->json(['ok' => true]);
    }
}
