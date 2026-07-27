<?php

namespace App\Exceptions;

use App\MoonShine\Pages\Errors\ErrorPage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MoonShine\Crud\Exceptions\NotFoundException;

// Класс для обработки ошибок 404 (страница не найдена).
class MVNotFoundException extends NotFoundException
{
    // Метод, отвечающий за отображение пользовательской страницы ошибки 404.
    public function render(Request $request): Response
    {
        // Получаем страницу ошибки 404 из конфигурации MoonShine
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
