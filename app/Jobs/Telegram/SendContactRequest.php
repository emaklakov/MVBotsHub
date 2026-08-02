<?php

namespace App\Jobs\Telegram;

use App\Domain\Bots\Models\Bot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendContactRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Bot $bot,
        public int|string $chatId,
    ) {}

    public function handle(): void
    {
        try {
            Http::post("https://api.telegram.org/bot{$this->bot->token}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => 'Для продолжения работы, пожалуйста, поделитесь вашим номером телефона.',
                'reply_markup' => json_encode([
                    'keyboard' => [
                        [
                            [
                                'text' => '📱 Поделиться контактом',
                                'request_contact' => true,
                            ]
                        ]
                    ],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ]),
            ]);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
