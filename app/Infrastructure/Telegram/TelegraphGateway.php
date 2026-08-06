<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use App\Application\Services\LogService;
use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Contracts\TelegramGatewayInterface;
use App\Domain\Bots\Models\Bot;
use App\Domain\Broadcasts\Exceptions\RateLimitException;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Http\Client\RequestException;

final class TelegraphGateway implements TelegramGatewayInterface
{
    public function send(SendMessage $sendMessage): ?int
    {
        try {
            $telegraph = Telegraph::bot($sendMessage->bot->token)
                ->chat((string) $sendMessage->chatId)
                ->html($sendMessage->text);

            if ($sendMessage->hasKeyboard()) {
                $telegraph = $telegraph->keyboard($sendMessage->keyboard());
            }

            $response = $telegraph->send();

            if (!$response->successful() || $response->json('ok') !== true) {
                LogService::logWarning('Telegram API non-ok response', [
                    'bot_id' => $sendMessage->botId,
                    'response' => $response->body(),
                ]);
                return null;
            }

            return (int)$response->json('result.message_id');
        } catch (RequestException $exception) {
            $response = $exception->response;

            if ($response->status() === 429) {
                $retryAfter = $response->json('parameters.retry_after', 60);
                throw new RateLimitException($retryAfter);
            }

            LogService::logError('Telegram API error', [
                'bot_id' => $sendMessage->botId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                sprintf('Telegram API error %d: %s', $response->status(), $response->body()),
                $response->status(),
                $exception
            );
        }
    }

    public function answerCallbackQuery(Bot $bot, int $callbackQueryId): void
    {
        Telegraph::bot($bot->token)->replyWebhook(callbackQueryId: $callbackQueryId);
    }
}
