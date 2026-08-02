<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Bots\Bot\Pages;

use App\Domain\Bots\Enums\BotStatus;
use App\Domain\Bots\Enums\WebhookStatus;
use App\Domain\Bots\Models\Bot;
use App\Domain\Bots\Services\WebhookRegistrar;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Bots\BotMember\BotMemberResource;
use Illuminate\Support\Facades\Gate;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\DetailPage;
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
            Text::make('Имя пользователя', 'name'),
            Text::make('Название бота', 'title'),
            Textarea::make('Описание', 'description'),
            Enum::make('Статус', 'status')->attach(BotStatus::class),
            Preview::make('Статус токена', 'bot_token_status', fn($item) => !empty($item->token)
                ? 'Установлен (••••' . substr($item->token, -4) . ')'
                : 'Не задан'
            )->badge(fn($value) => !empty($value) ? 'green' : 'gray'),
            Enum::make('Webhook', 'webhook_status')->attach(WebhookStatus::class),
            Text::make('Webhook URL', 'webhook_url'),
            Text::make('Webhook Secret', 'webhook_secret_token'),
            Json::make('Настройки', 'settings')->fields([
                Position::make(),
                Text::make('Имя', 'name'),
                Text::make('Ключ', 'key'),
                Text::make('Значение', 'value'),
                Json::make('Данные', 'data')
                    ->keyValue(),
                Switcher::make('Включено', 'is_active'),
            ])
                ->default([]),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
            HasMany::make('Доступы к боту', 'members', resource: BotMemberResource::class)
                ->modifyEditButton(fn($button) => $button->canSee(fn() => false))
                ->creatable()
                ->tabMode(),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons()->add(
            ActionButton::make('Зарегистрировать Webhook')
                ->method('registerWebhook')
                ->canSee(fn () => Gate::allows('registerWebhook', Bot::class))
                ->icon('globe-alt')
                ->class('py-[10px] ml-4 btn-secondary'),
        );
    }

    public function registerWebhook(Bot $bot): void
    {
        $registrar = app(WebhookRegistrar::class);
        $success = $registrar->register($bot);

        if ($success) {
            $this->toast('Webhook registered successfully', 'success');
        } else {
            $this->toast('Failed to register webhook. Check bot token.', 'error');
        }
    }
}
