<?php

use App\MoonShine\Pages\Errors\ErrorPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require base_path('routes/moonshine-register.php');
            require base_path('routes/moonshine-services.php');
            require base_path('routes/moonshine-notifications.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, $request) {

            if($e instanceof HttpExceptionInterface) {
                $status = $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : 500;

                $messages = [
                    400 => [
                        'Некорректный запрос. Проверьте введённые данные и попробуйте снова.',
                        'Bad Request',
                    ],
                    401 => [
                        'Необходима авторизация. Пожалуйста, войдите в систему.',
                        'Unauthorized',
                    ],
                    402 => [
                        'Необходима оплата для доступа к этому разделу.',
                        'Payment Required',
                    ],
                    403 => [
                        'У вас нет прав для просмотра этой страницы.',
                        'Forbidden',
                    ],
                    404 => [
                        'Такой страницы не существует или у вас нет к ней доступа.',
                        'Not Found',
                    ],
                    405 => [
                        'Метод запроса не поддерживается для этого адреса.',
                        'Method Not Allowed',
                    ],
                    408 => [
                        'Время ожидания запроса истекло. Попробуйте снова.',
                        'Request Timeout',
                    ],
                    409 => [
                        'Конфликт данных. Возможно, информация уже была изменена другим пользователем.',
                        'Conflict',
                    ],
                    413 => [
                        'Слишком большой размер загружаемых данных.',
                        'Payload Too Large',
                    ],
                    415 => [
                        'Неподдерживаемый тип файла или формата данных.',
                        'Unsupported Media Type',
                    ],
                    419 => [
                        'Истекла сессия. Обновите страницу и попробуйте снова.',
                        'Page Expired',
                    ],
                    422 => [
                        'Введённые данные некорректны. Проверьте форму и попробуйте снова.',
                        'Unprocessable Entity',
                    ],
                    429 => [
                        'Слишком много запросов. Подождите немного и попробуйте снова.',
                        'Too Many Requests',
                    ],
                    500 => [
                        'Внутренняя ошибка сервера. Мы уже знаем о проблеме.',
                        'Server Error',
                    ],
                    502 => [
                        'Сервер получил некорректный ответ. Попробуйте позже.',
                        'Bad Gateway',
                    ],
                    503 => [
                        'Сервис временно недоступен. Ведутся технические работы.',
                        'Service Unavailable',
                    ],
                    504 => [
                        'Сервер не дождался ответа. Попробуйте позже.',
                        'Gateway Timeout',
                    ],
                ];

                $message = $messages[$status] && $messages[$status][0] ? $messages[$status][0] : 'Произошла непредвиденная ошибка.';
                $message_en = $messages[$status] && $messages[$status][1] ? $messages[$status][1] : 'Oops!';

                $page = moonshineConfig()->getPage('error', ErrorPage::class)
                    ->code($status)
                    ->message_en($message_en)
                    ->title("Ошибка {$status} - {$message_en}")
                    ->message($message);

                return response((string) $page, $status);
            }

            return null;
        });
    })->create();
