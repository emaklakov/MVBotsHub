<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserLog;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\UserLog;
use App\MoonShine\Resources\UserLog\Pages\UserLogIndexPage;
use App\MoonShine\Resources\UserLog\Pages\UserLogDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<UserLog, UserLogIndexPage, UserLogDetailPage>
 */
class UserLogResource extends ModelResource
{
    protected bool $withPolicy = true;

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
}
