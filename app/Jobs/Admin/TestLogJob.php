<?php

namespace App\Jobs\Admin;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public bool $shouldFail = false
    ) {}

    public function handle(): void
    {
        if ($this->shouldFail) {
            throw new \Exception('Тестовая ошибка для журнала');
        }

        // Имитация работы
        sleep(2);
    }
}
