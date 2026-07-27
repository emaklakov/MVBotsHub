<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;

/**
 * Middleware для проверки срока действия пароля пользователя:
 * если пароль истек, выполняется выход из системы и перенаправление на страницу запроса сброса пароля.
 */
class CheckExpiredPassword
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth('moonshine')->check()) {

            $user = auth('moonshine')->user();
            $expirationDays = config('auth.passwords.expired', 90);
            $passwordChangedAt = new Carbon(($user->password_changed_at) ? $user->password_changed_at : $user->created_at);

            if ($passwordChangedAt->lte(now()->subDays($expirationDays))) {
                auth('moonshine')->logout();

                $message = 'Срок действия вашего пароля истек, пожалуйста, измените его.';

                return redirect()->route('password.request')->withMessage($message);
            }
        }

        return $next($request);
    }
}
