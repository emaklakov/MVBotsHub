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

        $bot = \App\Domain\Bots\Models\Bot::find(2);

        $this->info(json_encode($bot));

        $flow = \App\Domain\Flows\Models\Flow::find(3);

        $this->info(json_encode($flow));

        $this->info(json_encode($flow->bot_id));

        $this->newLine();
        $this->info("Готово.");

        return self::SUCCESS;
    }
}
