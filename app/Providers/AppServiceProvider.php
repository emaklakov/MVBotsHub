<?php

namespace App\Providers;

use App\Listeners\LogUserLogout;
use App\Listeners\SendTwoFactorCode;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use MoonShine\Laravel\Http\Controllers\ProfileController as BaseProfileController;
use App\Http\Controllers\Admin\ProfileController;
use MoonShine\Laravel\Http\Controllers\AuthenticateController as BaseAuthenticateController;
use App\Http\Controllers\Auth\AuthenticateController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BaseAuthenticateController::class, AuthenticateController::class);
        $this->app->bind(BaseProfileController::class, ProfileController::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
