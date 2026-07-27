<?php

namespace App\Console\Commands;

use App\Jobs\TestLogJob;
use Illuminate\Console\Command;

class TestCodeCommand extends Command
{
    protected $signature = 'test:code';

    protected $description = 'Тестирование кода';

    public function handle(): int
    {
        $this->info('Тестирование запущено');
        $this->newLine();

        //\App\Jobs\TestLogJob::dispatch(1);
        //\App\Jobs\TestLogJob::dispatch(1)->onConnection('sync');
        //\App\Jobs\TestLogJob::dispatch(2, shouldFail: true);

        for ($i = 0; $i < 100; $i++) {
            TestLogJob::dispatch($i);
        }

        $this->newLine();
        $this->info("Готово.");

        return self::SUCCESS;
    }
}
