<?php

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

if (config('moonshine-register.enabled', true)) {
    Route::moonshine(static function (Router $router): void {
        $route = trim((string) config('moonshine-register.route', 'register'), '/');
        $passwordResetRoute = trim((string) config('moonshine-register.password_reset.route', 'forgot-password'), '/');
        $passwordResetTokenRoute = trim((string) config('moonshine-register.password_reset.reset_route', 'reset-password'), '/');

        Route::middleware('guest:' . moonshineConfig()->getGuard())
            ->controller(RegisterController::class)
            ->group(static function () use ($route): void {
                Route::get($route, 'create')->name('register');
                Route::post($route, 'store')->name('register.store');
            });

        if (config('moonshine-register.password_reset.enabled', true)) {
            Route::middleware('guest:' . moonshineConfig()->getGuard())
                ->controller(PasswordResetController::class)
                ->group(static function () use ($passwordResetRoute, $passwordResetTokenRoute): void {
                    Route::get($passwordResetRoute, 'create')->name('password.request');
                    Route::post($passwordResetRoute, 'store')->name('password.email');
                    Route::get($passwordResetTokenRoute . '/{token}', 'reset')->name('password.reset');
                    Route::post($passwordResetTokenRoute . '/{token}', 'update')->name('password.update');
                });
        }
    });
}

if (config('moonshine-two-factor.enabled', true)) {
    Route::moonshine(static function (Router $router): void {
        $twoFactorRoute = trim((string) config('moonshine-two-factor.route', '2fa'), '/');
        $twoFactorResendRoute = trim((string) config('moonshine-two-factor.resend_route', '2fa/resend'), '/');

        Route::middleware('auth:' . moonshineConfig()->getGuard())
            ->controller(TwoFactorController::class)
            ->group(static function () use ($twoFactorRoute, $twoFactorResendRoute): void {
                Route::get($twoFactorRoute, 'show')->name('twoFactor.show');
                Route::post($twoFactorRoute, 'verify')->name('twoFactor.verify');
                Route::post($twoFactorResendRoute, 'resend')->name('twoFactor.resend');
            });
    });
}
