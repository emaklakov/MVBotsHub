<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Contracts\Notifications\EnhancedMoonShineNotificationContract;
use App\MoonShine\Notifications\QueuedMoonShineNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use MoonShine\Crud\Contracts\Notifications\MoonShineNotificationContract;

final class MoonShineNotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MoonShineNotificationContract::class,
            QueuedMoonShineNotification::class,
        );

        $this->app->singleton(
            EnhancedMoonShineNotificationContract::class,
            QueuedMoonShineNotification::class,
        );
    }

    public function boot(): void
    {
        RateLimiter::for('moonshine-notifications-send', function () {
            return Limit::perMinute(30)->by(
                auth(config('moonshine.auth.guard'))?->id() ?: request()->ip()
            );
        });

        RateLimiter::for('moonshine-notifications-poll', function () {
            return Limit::perMinute(60)->by(
                auth(config('moonshine.auth.guard'))?->id() ?: request()->ip()
            );
        });
    }
}
