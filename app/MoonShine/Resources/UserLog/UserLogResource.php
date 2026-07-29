<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserLog;

use App\Models\Admin\User\UserLog;
use App\MoonShine\Resources\Base\BaseResource;
use App\MoonShine\Resources\Concerns\HasPerPageSession;
use App\MoonShine\Resources\UserLog\Pages\UserLogDetailPage;
use App\MoonShine\Resources\UserLog\Pages\UserLogIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<UserLog, UserLogIndexPage, UserLogDetailPage>
 */
class UserLogResource extends BaseResource
{
    protected string $model = UserLog::class;

    protected string $title = 'Логи действий';

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            UserLogIndexPage::class,
            UserLogDetailPage::class,
        ];
    }

    protected function exportFields(): iterable
    {
        return [
            ID::make(),

            Date::make('Дата', 'created_at')
                ->modifyRawValue(static fn (mixed $raw, UserLog $data, Date $ctx) => $data->created_at?->format('d.m.Y H:i:s') ?? ''),

            Text::make('Пользователь', 'user.name')
                ->modifyRawValue(static fn (mixed $raw, UserLog $data, Text $ctx) => $data->user?->name ?? '—'),

            Text::make('Действие', 'action'),

            Text::make('Объект', 'subject_type')
                ->modifyRawValue(static fn (mixed $raw, UserLog $data, Text $ctx) => $data->subject_type
                    ? class_basename($data->subject_type) . ' #' . $data->subject_id
                    : '—'),

            Text::make('IP', 'ip_address'),
        ];
    }
}
