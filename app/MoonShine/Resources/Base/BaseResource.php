<?php

namespace App\MoonShine\Resources\Base;

use App\MoonShine\Resources\Traits\HasPerPageSession;
use Illuminate\Support\Facades\Log;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Resources\ModelResource;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

abstract class BaseResource extends ModelResource implements HasImportExportContract
{
    use ImportExportConcern, HasPerPageSession;

    protected bool $withPolicy = true;
    protected int $itemsPerPage = 10;

    public function modifyErrorResponse(Response $response, Throwable $exception): Response
    {
        Log::error('Ошибка - App\MoonShine\Resources\Base\BaseResource::modifyErrorResponse', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        return $response;
    }

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
