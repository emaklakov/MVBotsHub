<?php

namespace App\Domain\Conversations\Services;

use App\Domain\Bots\Models\Bot;
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
            // 1. Найти или создать People
            $people = Person::firstOrCreate(
                ['phone' => $phone],
                ['language' => $bot->settings['language'] ?? config('app.locale')]
            );

            // 2. Ищем старого подписчика этого бота с тем же people_id
            $oldSubscriber = BotSubscriber::where('bot_id', $bot->id)
                ->where('people_id', $people->id)
                ->where('id', '!=', $newSubscriber->id)
                ->first();

            if ($oldSubscriber) {
                // Переносим историю сообщений
                $oldConversations = Conversation::where('bot_subscriber_id', $oldSubscriber->id)->get();

                foreach ($oldConversations as $oldConv) {
                    $newConv = Conversation::create([
                        'bot_subscriber_id' => $newSubscriber->id,
                        'bot_id' => $bot->id,
                        'status' => 'closed', // старые диалоги закрываем
                        'context' => [],
                    ]);

                    Message::where('conversation_id', $oldConv->id)
                        ->update(['conversation_id' => $newConv->id]);

                    $oldConv->delete();
                }

                // Переносим настройки и язык
                $newSubscriber->settings = $oldSubscriber->settings;
                $newSubscriber->language = $oldSubscriber->language ?? $people->language;

                // Старый подписчик → merged
                $oldSubscriber->update([
                    'status' => 'merged',
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
                $newSubscriber->language = $people->language
                    ?? $bot->settings['language']
                    ?? config('app.locale');
            }

            // Привязываем people
            $newSubscriber->people_id = $people->id;
            $newSubscriber->save();

            // СЕССИЮ НЕ ПЕРЕНОСИМ — сбрасываем в начало
            // Закрываем текущую активную conversation
            Conversation::where('bot_subscriber_id', $newSubscriber->id)
                ->where('status', 'active')
                ->update(['status' => 'closed']);
        });
    }
}
