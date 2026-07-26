<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorVerified
{
    protected array $allowedRoutes = [
        'moonshine.twoFactor.show',
        'moonshine.twoFactor.verify',
        'moonshine.twoFactor.resend',
        'moonshine.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! session('needs_2fa')) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), $this->allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('moonshine.twoFactor.show');
    }
}
