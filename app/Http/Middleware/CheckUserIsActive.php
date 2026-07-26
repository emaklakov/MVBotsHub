<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('moonshine');

        if ($guard->check() && !$guard->user()->is_active) {
            $guard->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('moonshine.login')
                ->with('status', __('Вы зарегистрированы. Обратитесь к администратору Личного кабинета для назначения вам доступов.'));
        }

        return $next($request);
    }
}
