<?php

namespace App\Console\Commands;

use App\Models\Users\Enums\NotificationPriority;
use App\Models\Users\Role;
use App\MoonShine\Contracts\Notifications\EnhancedMoonShineNotificationContract;
use App\MoonShine\Notifications\NotificationTemplate;
use Illuminate\Console\Command;
use MoonShine\Crud\Notifications\NotificationButton;

class TestCodeCommand extends Command
{
    protected $signature = 'test:code';

    protected $description = 'Тестирование кода';

    public function handle(EnhancedMoonShineNotificationContract $notification): int
    {
        $this->info('Тестирование запущено');
        $this->newLine();

        //\App\Jobs\TestLogJob::dispatch(1);
        //\App\Jobs\TestLogJob::dispatch(1)->onConnection('sync');
        //\App\Jobs\TestLogJob::dispatch(2, shouldFail: true);

        // 1. Создаём бота (если ещё нет) или берём существующего
        $bot = \App\Domain\Bots\Models\Bot::first();

        // 2. Создаём Flow с триггером /start
        $flow = \App\Domain\Flows\Models\Flow::create([
            'bot_id' => $bot->id,
            'name' => 'Приветствие',
            'trigger_type' => 'command',
            'trigger_value' => 'start',
            'status' => 'active',
        ]);

        // 3. Сохраняем draft-схему
        $draft = \App\Domain\Flows\Models\FlowVersion::updateOrCreate(
            [
                'flow_id' => $flow->id,
                'status' => 'draft',
            ],
            [
                'schema' => [
                    'start_block_id' => 'block_1',
                    'blocks' => [
                        'block_1' => [
                            'id' => 'block_1',
                            'type' => 'text',
                            'content' => [
                                'translations' => [
                                    'ru' => 'Привет! Как тебя зовут?',
                                    'en' => "Hi! What's your name?",
                                ],
                            ],
                            'next_id' => 'block_2',
                        ],
                        'block_2' => [
                            'id' => 'block_2',
                            'type' => 'input',
                            'config' => [
                                'variable' => 'user_name',
                                'hint' => 'Введите ваше имя',
                            ],
                            'next_id' => 'block_3',
                        ],
                        'block_3' => [
                            'id' => 'block_3',
                            'type' => 'text',
                            'content' => [
                                'translations' => [
                                    'ru' => 'Приятно познакомиться!',
                                    'en' => 'Nice to meet you!',
                                ],
                            ],
                            'next_id' => null,
                        ],
                    ],
                ],
                'version_number' => 0,
            ]
        );

        // 4. Публикуем (копируем draft → published)
        $published = \App\Domain\Flows\Models\FlowVersion::create([
            'flow_id' => $flow->id,
            'schema' => $draft->schema,
            'status' => 'published',
            'version_number' => 1,
            'published_at' => now(),
            'published_by' => 1, // ID админа
        ]);

        $this->info("Flow ID: {$flow->id}, Published version: {$published->version_number}\n");

        $this->newLine();
        $this->info("Готово.");

        return self::SUCCESS;
    }
}
