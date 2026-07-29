<?php

namespace App\Console\Commands;

use App\Jobs\TestLogJob;
use App\MoonShine\Contracts\Notifications\EnhancedMoonShineNotificationContract;
use App\MoonShine\Notifications\NotificationTemplate;
use Illuminate\Console\Command;
use MoonShine\Laravel\Notifications\MoonShineNotification;
use MoonShine\Crud\Notifications\NotificationButton;
use MoonShine\Support\Enums\Color;
use App\Enums\NotificationPriority;

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

        // 1. Всем админам (уходит в очередь notifications)
        $notification->sendToAll(
            message: 'Тестовое уведомление из консоли',
            color: 'green',
            icon: 'check-circle',
            priority: NotificationPriority::NORMAL,
            category: 'system.test',
        );

        // 2. Конкретным пользователям
        $notification->sendToMany(
            message: 'Привет, менеджеры!',
            ids: [1, 2, 3],
            button: new NotificationButton(
                label: 'Открыть панель',
                link: route('moonshine.index'),
            ),
            priority: NotificationPriority::HIGH,
            category: 'orders.new',
            groupKey: 'orders.new',
        );

        // 3. Через шаблон
        $notification->sendTemplate(
            template: NotificationTemplate::systemError('Ошибка в консольной команде'),
        );

        // 4. С TTL (автоудаление через 24 часа)
        $notification->sendToAll(
            message: 'Временное уведомление',
            priority: NotificationPriority::LOW,
            expiresAt: now()->addHours(24)->toDateTimeImmutable(),
        );

        $this->components->info('Уведомления отправлены в очередь.');

        // Проверка: сколько непрочитанных у админа #1
        $count = $notification->countUnreadForUser(1);
        $this->components->info("Не прочитано у админа #1: {$count}");

        $this->newLine();
        $this->info("Готово.");

        return self::SUCCESS;
    }
}
