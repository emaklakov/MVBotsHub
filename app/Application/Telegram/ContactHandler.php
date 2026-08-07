<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Bots\Services\SystemMessageResolver;
use App\Application\Conversations\Services\PhoneMergeService;
use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Enums\SystemMessageKey;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Flows\Contracts\MessageSenderInterface;
use App\Infrastructure\Telegram\DTO\Contact as TelegramContact;

final class ContactHandler
{
    public function __construct(
        private readonly PhoneMergeService $phoneMergeService,
        private readonly MessageSenderInterface $messageSender,
        private readonly SystemMessageResolver $systemMessages,
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
            // Язык ещё не привязан к Person на этом этапе (merge ниже не выполнялся),
            // резолвер откатится на subscriber->language / bot->settings['language'] / fallback.
            $errorText = $this->systemMessages->resolve($bot, SystemMessageKey::NOT_YOUR_CONTACT, $subscriber);

            $this->messageSender->send(new SendMessage($bot, $subscriber->telegram_id, $errorText, $conversation->id));
            $this->messageSender->flush();

            return;
        }

        $this->phoneMergeService->merge(
            $subscriber,
            $contact->phoneNumber(),
            $bot
        );

        // merge() обновляет $subscriber->language в этой же инстанции (перенос от
        // старого подписчика/Person или дефолт бота) — resolve() вызывается ПОСЛЕ
        // merge(), поэтому effectiveLanguage здесь уже актуален.
        $welcomeText = $this->systemMessages->resolve($bot, SystemMessageKey::WELCOME, $subscriber);

        $this->messageSender->send(new SendMessage($bot, $subscriber->telegram_id, $welcomeText, $conversation->id, replyKeyboardHide: true));
        $this->messageSender->flush();
    }
}
