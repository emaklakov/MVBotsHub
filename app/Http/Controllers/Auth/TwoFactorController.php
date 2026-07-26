<?php

namespace App\Http\Controllers\Auth;

use App\MoonShine\Pages\Auth\TwoFactorPage;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Контроллер для управления процессом двухфакторной аутентификации пользователя:
 * отображение страницы проверки, проверка кода, повторная отправка кода и лимитирование попыток.
 */
class TwoFactorController
{
    protected int $maxAttempts = 5;
    protected int $decaySeconds = 900; // 15 минут

    /**
     * Отображает страницу двухфакторной аутентификации.
     * Проверяет, включен ли функционал двухфакторной аутентификации и есть ли активная сессия пользователя.
     */
    public function show()
    {
        $page = moonshine()->getContainer(TwoFactorPage::class);

        if ($page->isResponseModified()) {
            return $page->getModifiedResponse();
        }

        return $page->render();
    }

    /**
     * Обрабатывает запрос на двухфакторную аутентификацию.
     * Проверяет код двухфакторной аутентификации и возвращает соответствующий ответ.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = Auth::guard('moonshine')->user();
        $key = $this->throttleKey($user->getAuthIdentifier());

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'code' => __('two_factor.locked', ['minutes' => (int) ceil($seconds / 60)]),
            ]);
        }

        if (! $user->checkTwoFactorCode($request->input('code'))) {
            RateLimiter::hit($key, $this->decaySeconds);

            $left = $this->maxAttempts - RateLimiter::attempts($key);

            return back()->withErrors([
                'code' => $left > 0
                    ? __('two_factor.wrong_code_left', ['left' => $left])
                    : __('two_factor.locked', ['minutes' => (int) ceil($this->decaySeconds / 60)]),
            ]);
        }

        RateLimiter::clear($key);

        $user->clearTwoFactorCode();

        session()->forget(['needs_2fa', 'needs_2fa_user_id']);

        return redirect()->route('moonshine.index');
    }

    /**
     * Обрабатывает запрос на повторную отправку кода двухфакторной аутентификации.
     * Отправляет новый код на электронную почту пользователя.
     */
    public function resend(): RedirectResponse
    {
        $user = Auth::guard('moonshine')->user();
        $key = $this->throttleKey($user->getAuthIdentifier());

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'code' => __('two_factor.locked', ['minutes' => (int) ceil($seconds / 60)]),
            ]);
        }

        if (session('2fa_resent_at') && now()->diffInSeconds(session('2fa_resent_at')) < 60) {
            return back()->withErrors(['code' => __('two_factor.resend_throttled')]);
        }

        $code = $user->generateTwoFactorCode();
        $user->notify(new TwoFactorCodeNotification($code));

        session(['2fa_resent_at' => now()]);

        return back()->with('status', __('two_factor.resend_success'));
    }

    protected function throttleKey(int|string $userId): string
    {
        return "2fa-attempts:{$userId}";
    }
}
