<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Telegram\Flows\Flow\Pages;

use App\Domain\Flows\Enums\FlowStatus;
use App\Domain\Flows\Enums\FlowVersionStatus;
use App\Domain\Flows\Enums\TriggerTypes;
use App\Domain\Flows\Models\Flow;
use App\Domain\Flows\Models\FlowVersion;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Telegram\Bots\Bot\BotResource;
use App\MoonShine\Resources\Telegram\Flows\Flow\FlowResource;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;


/**
 * @extends DetailPage<FlowResource>
 */
class FlowDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Бот', 'bot', resource: BotResource::class, formatted: 'username'),
            Text::make('Название', 'name'),
            Enum::make('Тип триггера', 'trigger_type')->attach(TriggerTypes::class),
            Text::make('Значение триггера', 'trigger_value'),
            Enum::make('Статут', 'status')->attach(FlowStatus::class)->default(FlowStatus::DRAFT),
            Date::make(__('moonshine::ui.resource.created_at'), 'created_at')
                ->format('d.m.Y H:i:s'),
            Date::make(__('moonshine::ui.resource.updated_at'), 'updated_at')
                ->format('d.m.Y H:i:s'),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons()->add(
            ActionButton::make('Опубликовать')
                ->method('publishFlow')
                ->withConfirm(
                    title: 'Подтвердить',
                    content: 'Вы уверены, что хотите опубликовать?',
                    button: 'Подтвердить',
                )
                ->icon('upload', path: 'icons')
                ->canSee(fn(Flow $flow) => $flow->status === FlowStatus::ACTIVE)
                ->class('py-[10px] ml-4 btn-success'),
        );
    }

    #[AsyncMethod]
    public function publishFlow(CrudRequestContract $request, JsonResponse $response): JsonResponse
    {
        $flow = $request->getResource()->getItem();

        $draft = $flow->versions()->where('status', FlowVersionStatus::DRAFT)->latest()->first();

        if (!$draft) {
            return $response->toast('Черновая версия не найдена. Сначала сохраните схему в редакторе.', ToastType::ERROR);
        }

        $nextVersion = ($flow->versions()->max('version_number') ?? 0) + 1;

        FlowVersion::create([
            'flow_id' => $flow->id,
            'schema' => $draft->schema,
            'status' => FlowVersionStatus::PUBLISHED,
            'version_number' => $nextVersion,
            'published_at' => now(),
            'published_by' => auth()->id(),
        ]);

        return $response->toast("Поток опубликован как версия {$nextVersion}", ToastType::SUCCESS);
    }
}
