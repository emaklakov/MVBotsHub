<?php

declare(strict_types=1);

namespace App\Console\Commands\Broadcasts;

use App\Application\Broadcasts\Services\BroadcastDispatcher;
use App\Application\Broadcasts\Services\BroadcastRecipientGenerator;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Models\Broadcast;
use Illuminate\Console\Command;

/**
 * У Broadcast есть поле scheduled_at (обязательное в форме админки — см.
 * BroadcastFormPage), но до этой команды ничто не проверяло, наступило ли
 * время его запуска. Регистрируется в routes/console.php на ->everyMinute().
 */
final class DispatchScheduledBroadcasts extends Command
{
    protected $signature = 'broadcasts:dispatch-scheduled';

    protected $description = 'Запускает рассылки, у которых наступило scheduled_at';

    public function handle(
        BroadcastRecipientGenerator $generator,
        BroadcastDispatcher $dispatcher,
    ): int {
        $broadcasts = Broadcast::query()
            ->where('status', BroadcastStatus::PENDING)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($broadcasts as $broadcast) {
            $generator->generate($broadcast);
            $dispatcher->dispatchAll($broadcast);

            $this->info("Запущена рассылка #{$broadcast->id} ({$broadcast->name})");
        }

        return self::SUCCESS;
    }
}
