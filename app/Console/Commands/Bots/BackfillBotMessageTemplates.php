<?php

declare(strict_types=1);

namespace App\Console\Commands\Bots;

use App\Domain\Bots\Enums\SystemMessageKey;
use App\Domain\Bots\Models\Bot;
use App\Domain\Bots\Models\BotMessageTemplate;
use Illuminate\Console\Command;

/**
 * Разовая команда для деплоя миграции bot_message_templates: заводит
 * дефолтные переводы для ботов, созданных ДО появления BotObserver
 * (он покрывает только вновь создаваемых ботов).
 *
 * php artisan bots:backfill-message-templates
 */
final class BackfillBotMessageTemplates extends Command
{
    protected $signature = 'bots:backfill-message-templates';

    protected $description = 'Создаёт недостающие BotMessageTemplate с дефолтными переводами для существующих ботов';

    public function handle(): int
    {
        $created = 0;

        Bot::query()->chunkById(100, function ($bots) use (&$created) {
            foreach ($bots as $bot) {
                $existingKeys = BotMessageTemplate::query()
                    ->where('bot_id', $bot->id)
                    ->pluck('key')
                    ->map(fn ($key) => $key instanceof SystemMessageKey ? $key->value : $key)
                    ->all();

                $rows = collect(SystemMessageKey::cases())
                    ->reject(fn (SystemMessageKey $key) => in_array($key->value, $existingKeys, true))
                    ->map(fn (SystemMessageKey $key) => [
                        'bot_id' => $bot->id,
                        'key' => $key->value,
                        'translations' => json_encode($key->fallback()),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($rows->isNotEmpty()) {
                    BotMessageTemplate::query()->insert($rows->toArray());
                    $created += $rows->count();
                }
            }
        });

        $this->info("Создано шаблонов: {$created}");

        return self::SUCCESS;
    }
}
