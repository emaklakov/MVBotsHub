<?php

namespace App\MoonShine\Resources\Base;

use App\MoonShine\Resources\Concerns\HasPerPageSession;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Resources\ModelResource;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseResource extends ModelResource implements HasImportExportContract
{
    use ImportExportConcern, HasPerPageSession;

    protected bool $withPolicy = true;
    protected int $itemsPerPage = 10;

    protected function export(): ?Handler
    {
        return null;
    }

    protected function import(): ?Handler
    {
        return null;
    }

    protected function paginate(): Paginator|CursorPaginator
    {
        $paginate = parent::paginate();

        if ($paginate instanceof LengthAwarePaginator) {
            $paginate->onEachSide(1);
        }

        return $paginate;
    }
}
