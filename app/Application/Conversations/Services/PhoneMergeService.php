<?php

namespace App\Application\Conversations\Services;

use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Enums\ConversationStatus;
use App\Domain\Conversations\Enums\BotSubscriberStatus;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Conversations\Models\Conversation;
use App\Domain\Conversations\Models\Message;
use App\Domain\CRM\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhoneMergeService
{
    public function merge(BotSubscriber $newSubscriber, string $phone, Bot $bot): void
    {
        DB::transaction(function () use ($newSubscriber, $phone, $bot) {
            // 1. Найти или создать Person (с блокировкой строки на время транзакции)
            $person = Person::where('phone', $phone)->lockForUpdate()->first();

            if (!$person) {
                $person = Person::create([
                    'phone' => $phone,
                    'language' => $bot->settings['language'] ?? config('app.locale'),
                ]);
            }

            // 2. Ищем старого подписчика этого бота с тем же person_id, с блокировкой строки
            $oldSubscriber = BotSubscriber::where('bot_id', $bot->id)
                ->where('person_id', $person->id)
                ->where('id', '!=', $newSubscriber->id)
                ->lockForUpdate()
                ->first();

            if ($oldSubscriber) {
                // Переносим историю сообщений
                $oldConversations = Conversation::where('bot_subscriber_id', $oldSubscriber->id)->get();

                foreach ($oldConversations as $oldConv) {
                    $newConv = Conversation::create([
                        'bot_subscriber_id' => $newSubscriber->id,
                        'bot_id' => $bot->id,
                        'status' => ConversationStatus::CLOSED, // старые диалоги закрываем
                        'context' => [],
                    ]);

                    Message::where('conversation_id', $oldConv->id)
                        ->update(['conversation_id' => $newConv->id]);

                    $oldConv->delete();
                }

                // Переносим настройки и язык
                $newSubscriber->settings = $oldSubscriber->settings;
                $newSubscriber->language = $oldSubscriber->language ?? $person->language;

                // Старый подписчик → merged
                $oldSubscriber->update([
                    'status' => BotSubscriberStatus::MERGED,
                    'merged_into_id' => $newSubscriber->id,
                ]);

                Log::info('Subscriber merged by phone', [
                    'bot_id' => $bot->id,
                    'old_id' => $oldSubscriber->id,
                    'new_id' => $newSubscriber->id,
                    'phone' => $phone,
                ]);
            } else {
                // Новый people — язык из people или бота
                $newSubscriber->language = $person->language
                    ?? $bot->settings['language']
                    ?? config('app.locale');
            }

            // Привязываем people
            $newSubscriber->person_id = $person->id;
            $newSubscriber->save();

            // СЕССИЮ НЕ ПЕРЕНОСИМ — сбрасываем в начало
            // Закрываем текущую активную conversation
            Conversation::where('bot_subscriber_id', $newSubscriber->id)
                ->where('status', ConversationStatus::ACTIVE)
                ->update(['status' => ConversationStatus::CLOSED]);
        });
    }
}
