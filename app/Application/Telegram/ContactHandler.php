<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Conversations\Services\PhoneMergeService;
use App\Infrastructure\Telegram\DTO\Contact as TelegramContact;

final class ContactHandler
{
    public function __construct(
        private readonly PhoneMergeService $phoneMergeService,
        private readonly TelegramMessageSender $messageSender,
    ) {}

    public function handle(Bot $bot, BotSubscriber $subscriber, TelegramContact $contact): void
    {
        $conversation = Conversation::create([
            'bot_subscriber_id' => $subscriber->id,
            'bot_id'            => $bot->id,
            'status'            => ConversationStatus::ACTIVE,
            'context'           => [],
        ]);

        if($contact->userId() != $subscriber->telegram_id) {
            $erroText = $bot->settings['not_your_contact_message'] ?? 'Вы поделились не своим номером';

            $this->messageSender->send($bot, $subscriber->telegram_id, $erroText, $conversation->id);

            return;
        }

        $this->phoneMergeService->merge(
            $subscriber,
            $contact->phoneNumber(),
            $bot
        );

        $welcomeText = $bot->settings['welcome_message'] ?? 'Вы успешно авторизованы.';

        $this->messageSender->send($bot, $subscriber->telegram_id, $welcomeText, $conversation->id);
    }
}
