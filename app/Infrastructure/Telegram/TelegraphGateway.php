<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use App\Application\Services\LogService;
use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Contracts\TelegramGatewayInterface;
use App\Domain\Bots\Models\Bot;
use App\Domain\Broadcasts\Exceptions\RateLimitException;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Http\Client\RequestException;

final class TelegraphGateway implements TelegramGatewayInterface
{
    public function send(SendMessage $sendMessage): ?int
    {
        try {
            $telegraph = Telegraph::bot($sendMessage->bot->token)
                ->chat((string) $sendMessage->chatId)
                ->html($sendMessage->text);

            if ($sendMessage->replyMarkup) {
                $telegraph = $telegraph->replyKeyboard($sendMessage->replyMarkup);
            } elseif ($sendMessage->inlineKeyboard) {
                $keyboard = $this->buildInlineKeyboard($sendMessage->inlineKeyboard);
                $telegraph = $telegraph->keyboard($keyboard);
            }

            $response = $telegraph->send();

            if (!$response->successful() || $response->json('ok') !== true) {
                LogService::logWarning('Telegram API non-ok response', [
                    'bot_id' => $sendMessage->bot->id,
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
                'bot_id' => $sendMessage->bot->id,
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

    /**
     * Создаёт Keyboard объект из массива, минуя Keyboard::fromArray()
     * которая теряет callback_data.
     *
     * @param array<int, array<int, array{text: string, callback_data?: string, url?: string}>> $inlineKeyboard
     */
    private function buildInlineKeyboard(array $inlineKeyboard): Keyboard
    {
        $keyboard = Keyboard::make();

        foreach ($inlineKeyboard as $row) {
            $buttons = [];
            foreach ($row as $btn) {
                $button = Button::make($btn['text']);

                if (isset($btn['callback_data'])) {
                    $button = $button->action($btn['callback_data']);
                }

                if (isset($btn['url'])) {
                    $button = $button->url($btn['url']);
                }

                $buttons[] = $button;
            }
            $keyboard = $keyboard->row($buttons);
        }

        return $keyboard;
    }

    public function answerCallbackQuery(Bot $bot, int $callbackQueryId): void
    {
        Telegraph::bot($bot->token)->replyWebhook(
            callbackQueryId: $callbackQueryId,
            message: ''
        );
    }
}
