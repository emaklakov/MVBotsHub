<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\Pages;

use App\Application\Broadcasts\Services\BroadcastDispatcher;
use App\Application\Broadcasts\Services\BroadcastRecipientGenerator;
use App\Domain\Broadcasts\Enums\BroadcastStatus;
use App\Domain\Broadcasts\Models\Broadcast;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Broadcasts\Broadcast\BroadcastResource;
use App\MoonShine\Resources\Telegram\Broadcasts\BroadcastRecipient\BroadcastRecipientResource;
use App\MoonShine\Resources\Telegram\Flows\FlowVersion\FlowVersionResource;
use Illuminate\Http\RedirectResponse;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;


/**
 * @extends DetailPage<BroadcastResource>
 */
class BroadcastDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name'),
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username'),
            BelongsTo::make('Поток', 'flowVersion', resource: FlowVersionResource::class, formatted: fn ($item) => $item->flow?->name.' ('.$item->version_number.')'),
            Enum::make('Статус', 'status')->attach(BroadcastStatus::class),
            Text::make('Всего', 'total_recipients'),
            Text::make('Отправлено', 'sent_count'),
            Text::make('С ошибками', 'failed_count'),
            Preview::make('Процесс', null, fn($item) => "{$item->sent_count}/{$item->total_recipients} ({$item->failed_count} failed)"),
            Date::make('Запланировано', 'scheduled_at')
                ->format('d.m.Y H:i:s'),
            Date::make('Запущено', 'started_at')
                ->format('d.m.Y H:i:s'),
            Date::make('Завершено', 'completed_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
            HasMany::make('Получатели рассылки', 'recipients', resource: BroadcastRecipientResource::class)
                ->tabMode(),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons()->add(
            ActionButton::make('Запустить')
                ->method('startBroadcast')
                ->withConfirm(
                    title: 'Подтвердить',
                    content: 'Вы уверены, что хотите запустить рассылку?',
                    button: 'Подтвердить',
                )
                ->canSee(fn(Broadcast $b) => $b->status === BroadcastStatus::PENDING)
                ->icon('play-circle')
                ->class('py-[10px] ml-4 btn-success'),

            ActionButton::make('Повторить с ошибкой')
                ->method('retryFailed')
                ->withConfirm(
                    title: 'Подтвердить',
                    content: 'Вы уверены, что хотите повторить отправления с ошибками?',
                    button: 'Подтвердить',
                )
                ->canSee(fn(Broadcast $b) => $b->status === BroadcastStatus::COMPLETED && $b->failed_count > 0)
                ->icon('arrow-path-rounded-square')
                ->class('py-[10px] ml-4 btn-success'),

            ActionButton::make('Остановить')
                ->method('cancelBroadcast')
                ->withConfirm(
                    title: 'Подтвердить',
                    content: 'Вы уверены, что хотите остановить рассылку?',
                    button: 'Подтвердить',
                )
                ->canSee(fn(Broadcast $b) => $b->status === BroadcastStatus::PROCESSING)
                ->icon('stop-circle')
                ->class('py-[10px] ml-4 btn-error'),
        );
    }

    #[AsyncMethod]
    public function startBroadcast(CrudRequestContract $request): RedirectResponse
    {
        $broadcast = $request->getResource()->getItem();

        // Генерируем получателей, если ещё нет
        $generator = app(BroadcastRecipientGenerator::class);
        $generator->generate($broadcast);

        $dispatcher = app(BroadcastDispatcher::class);
        $dispatcher->dispatchAll($broadcast);

        toast('Рассылка отправлена в очередь', ToastType::SUCCESS);

        return back();
    }

    #[AsyncMethod]
    public function retryFailed(CrudRequestContract $request): RedirectResponse
    {
        $broadcast = $request->getResource()->getItem();

        $dispatcher = app(BroadcastDispatcher::class);
        $dispatcher->retryFailed($broadcast);

        toast('Повторная попытка неудавшихся сообщений', ToastType::SUCCESS);

        return back();
    }

    #[AsyncMethod]
    public function cancelBroadcast(CrudRequestContract $request): RedirectResponse
    {
        $broadcast = $request->getResource()->getItem();

        $broadcast->update(['status' => BroadcastStatus::CANCELLED]);
        toast('Рассылка отменена', ToastType::SUCCESS);

        return back();
    }
}
