<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для установки безопасных HTTP-заголовков:
 * X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, Access-Control-Allow-Origin, Access-Control-Allow-Methods, Access-Control-Allow-Headers.
 */
class SecurityHeaders
{
    private $unwantedHeaders = ['X-Powered-By', 'server', 'Server'];

    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(self), microphone=(), geolocation=(self), payment=(), usb=()'
        );

        $response->headers->set('Access-Control-Allow-Origin', config('session.domain'));
        $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Requested-With,X-CSRF-Token');

        if (app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );

            $response->headers->set(
                'Content-Security-Policy',
                "block-all-mixed-content; frame-ancestors 'self'; object-src 'none'; base-uri 'self'"
            );
        }

        $this->removeUnwantedHeaders($this->unwantedHeaders, $response);

        return $response;
    }

    /**
     * @param $headers
     */
    private function removeUnwantedHeaders($headers, $response): void
    {
        foreach ($headers as $header) {
            header_remove($header);
        }
    }
}
