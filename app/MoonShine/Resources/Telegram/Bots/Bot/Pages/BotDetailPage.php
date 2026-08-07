<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\Bot\Pages;

use App\Application\Bots\Services\WebhookService;
use App\Domain\Bots\Enums\BotStatus;
use App\Domain\Bots\Enums\WebhookStatus;
use App\Domain\Bots\Models\Bot;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Bots\BotMember\BotMemberResource;
use App\MoonShine\Resources\Telegram\Bots\BotMessageTemplate\BotMessageTemplateResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Position;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;


/**
 * @extends DetailPage<BotResource>
 */
class BotDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Имя пользователя', 'username'),
            Text::make('Название бота', 'name'),
            Textarea::make('Описание', 'description'),
            Enum::make('Статус', 'status')->attach(BotStatus::class),
            Preview::make('Статус токена', 'bot_token_status', fn($item) => $item->maskedTokenPreview())
                ->badge(fn($value) => $value == 'set' ? 'green' : 'gray'),
            Enum::make('Webhook', 'webhook_status')->attach(WebhookStatus::class),
            Json::make('Настройки', 'settings')
                ->keyValue('Ключ', 'Значение')
                ->default([]),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
            HasMany::make('Доступы к боту', 'members', resource: BotMemberResource::class)
                ->creatable()
                ->tabMode(),
            HasMany::make('Системные сообщения ботов', 'messageTemplates', resource: BotMessageTemplateResource::class)
                ->creatable()
                ->tabMode(),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons()->add(
            ActionButton::make('Зарегистрировать Webhook')
                ->method('registerWebhook')
                ->withConfirm(
                    title: 'Подтвердить',
                    content: 'Вы уверены, что хотите Зарегистрировать Webhook?',
                    button: 'Подтвердить',
                )
                ->canSee(fn ($item) => Gate::allows('registerWebhook', Bot::class) && empty($item->webhook_url))
                ->icon('globe-alt')
                ->class('py-[10px] ml-4 btn-success'),
            ActionButton::make('Удалить Webhook')
                ->method('unregisterWebhook')
                ->withConfirm(
                    title: 'Подтвердить',
                    content: 'Вы уверены, что хотите Удалить Webhook?',
                    button: 'Подтвердить',
                )
                ->canSee(fn ($item) => Gate::allows('registerWebhook', Bot::class) && !empty($item->webhook_url))
                ->icon('globe-alt')
                ->class('py-[10px] ml-4 btn-error'),
        );
    }

    #[AsyncMethod]
    public function registerWebhook(CrudRequestContract $request): RedirectResponse
    {
        $bot = $request->getResource()->getItem();

        $webhookService = app(WebhookService::class);
        $success = $webhookService->register($bot);

        if ($success) {
            toast('Webhook успешно зарегистрирован.', ToastType::SUCCESS);
        } else {
           toast('Не удалось зарегистрировать Webhook. Проверьте токен бота и логи.', ToastType::ERROR);
        }

        return back();
    }

    #[AsyncMethod]
    public function unregisterWebhook(CrudRequestContract $request): RedirectResponse
    {
        $bot = $request->getResource()->getItem();

        $webhookService = app(WebhookService::class);
        $success = $webhookService->unregister($bot);

        if ($success) {
            toast('Webhook успешно удален.', ToastType::SUCCESS);
        } else {
            toast('Не удалось удалить Webhook. Проверьте логи.', ToastType::ERROR);
        }

        return back();
    }
}
