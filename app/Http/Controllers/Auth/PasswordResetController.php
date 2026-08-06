<?php

namespace App\Http\Controllers\Auth;

use App\Application\Users\Services\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\MoonShine\Pages\Auth\ForgotPasswordPage;
use App\MoonShine\Pages\Auth\ResetPasswordPage;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use MoonShine\Laravel\MoonShineAuth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Контроллер для управления процессом сброса пароля пользователя:
 * отображение страницы запроса сброса пароля, отправка ссылки для сброса пароля,
 * отображение страницы сброса пароля и обработка запроса на сброс пароля.
 */
class PasswordResetController extends Controller
{
    /**
     * Отображает страницу запроса сброса пароля.
     * Проверяет, включен ли функционал сброса пароля и есть ли активная сессия пользователя.
     */
    public function create(): Renderable|Response|string
    {
        abort_unless(config('moonshine-register.enabled', true) && config('moonshine-register.password_reset.enabled', true), 404);

        if (MoonShineAuth::getGuard()->check()) {
            return redirect()->route(moonshineConfig()->getHomeRoute());
        }

        $pageClass = config('moonshine-register.password_reset.request_page', ForgotPasswordPage::class);
        $page = moonshine()->getContainer($pageClass);

        if ($page->isResponseModified()) {
            return $page->getModifiedResponse();
        }

        return $page->render();
    }

    /**
     * Обрабатывает запрос на отправку ссылки для сброса пароля.
     * Отправляет ссылку на электронную почту пользователя для сброса пароля.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        abort_unless(config('moonshine-register.enabled', true) && config('moonshine-register.password_reset.enabled', true), 404);

        $broker = (string) config('moonshine-register.password_reset.broker', 'moonshine');
        Password::broker($broker)->sendResetLink($request->only('email'));

        return redirect()
            ->route('moonshine.password.request')
            ->with('status', __('register.reset_link_sent'));
    }

    /**
     * Отображает страницу сброса пароля.
     * Проверяет, включен ли функционал сброса пароля и есть ли активная сессия пользователя.
     */
    public function reset(string $token): Renderable|Response|string
    {
        abort_unless(config('moonshine-register.enabled', true) && config('moonshine-register.password_reset.enabled', true), 404);

        if (MoonShineAuth::getGuard()->check()) {
            return redirect()->route(moonshineConfig()->getHomeRoute());
        }

        $pageClass = config('moonshine-register.password_reset.reset_page', ResetPasswordPage::class);
        $page = moonshine()->getContainer($pageClass);

        if ($page->isResponseModified()) {
            return $page->getModifiedResponse();
        }

        return $page->render();
    }

    /**
     * Обрабатывает запрос на сброс пароля.
     * Сбрасывает пароль пользователя и возвращает соответствующий ответ.
     */
    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        abort_unless(config('moonshine-register.enabled', true) && config('moonshine-register.password_reset.enabled', true), 404);

        $broker = (string) config('moonshine-register.password_reset.broker', 'moonshine');

        $status = Password::broker($broker)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $passwordField = moonshineConfig()->getUserField('password', 'password');
                $user->{$passwordField} = Hash::make($password);
                $user->password_changed_at = Carbon::now()->toDateTimeString();
                $user->save();

                ActivityLogger::log('password_changed', $user, 'Пароль сброшен через восстановление');
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('moonshine.login')
                ->with('status', __('register.reset_success'));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => __('register.reset_failed'),
            ]);
    }
}
