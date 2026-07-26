<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MoonShine\Crud\Exceptions\NotFoundException;
use MoonShine\Laravel\Pages\ErrorPage;

class MVNotFoundException extends NotFoundException
{
    public function report(): void
    {
        //
    }

    public function render(Request $request): Response
    {
        $page = moonshineConfig()->getPage(
            'error',
            ErrorPage::class,
        )
            ->code(Response::HTTP_NOT_FOUND)
            ->message_en('Not Found')
            ->title("Ошибка 404 - Not Found")
            ->message('Такой страницы не существует или у вас нет к ней доступа.');

        return response((string) $page)->setStatusCode(Response::HTTP_NOT_FOUND);
    }
}
