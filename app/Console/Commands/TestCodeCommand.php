<?php

namespace App\Console\Commands;

use App\MoonShine\Contracts\Notifications\EnhancedMoonShineNotificationContract;
use Illuminate\Console\Command;

class TestCodeCommand extends Command
{
    protected $signature = 'test:code';

    protected $description = 'Тестирование кода';

    public function handle(EnhancedMoonShineNotificationContract $notification): int
    {
        $this->info('Тестирование запущено');
        $this->newLine();



        $this->newLine();
        $this->info("Готово.");

        return self::SUCCESS;
    }
}
