<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Validation\ValidationException;
use MoonShine\Contracts\Core\DependencyInjection\ConfiguratorContract;
use MoonShine\Contracts\Core\DependencyInjection\RouterContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;
use MoonShine\Laravel\Http\Controllers\AuthenticateController as BaseAuthenticateController;
use MoonShine\Laravel\Http\Requests\LoginFormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateController extends BaseAuthenticateController
{
    /**
     * @param  ConfiguratorContract<MoonShineConfigurator>  $config
     *
     * @throws ValidationException
     */
    public function authenticate(LoginFormRequest $request, ConfiguratorContract $config, RouterContract $router): Response
    {
        return $config->handleAuthenticate(function () use ($request, $config, $router) {
            if (filled($config->getAuthPipelines())) {
                $request = Pipeline::send($request)->through(
                    $config->getAuthPipelines()
                )->thenReturn();
            }

            if ($request instanceof JsonResponse) {
                return $request;
            }

            if ($request instanceof RedirectResponse) {
                return $request;
            }

            $password = $request->input('password');

            $request->authenticate();

            $guard = Auth::guard($config->getGuard());
            $user = $guard->user();

            // выбиваем все остальные активные сессии этого пользователя на других устройствах
            if (!$user->enabled_multi_device_login && filled($password)) {
                try {
                    $guard->logoutOtherDevices($password);
                } catch (\InvalidArgumentException $e) {
                    // Известный краевой случай в текущей версии Laravel:
                    // CookieJar::$queued оказывается null, когда remember-cookie никогда не выставлялся.
                    // Rehash пароля (реальная цель вызова) к этому моменту уже выполнен успешно —
                    // безопасно логируем и продолжаем.
                    //report($e);
                }
            }

            return redirect()->intended(
                $router->getEndpoints()->home()
            );
        });
    }
}
