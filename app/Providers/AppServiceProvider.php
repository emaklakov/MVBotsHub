<?php

namespace App\Providers;

use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\Users\ProfileController;
use App\Models\Jobs\JobLog;
use App\Models\Users\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use MoonShine\Laravel\Http\Controllers\AuthenticateController as BaseAuthenticateController;
use MoonShine\Laravel\Http\Controllers\ProfileController as BaseProfileController;

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
        date_default_timezone_set(config('app.timezone'));

        if(config('app.debug') == false) {
            \URL::forceScheme('https');
        }

        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Общий throttle для всех ботов приложения (Telegram лимитирует по IP)
        RateLimiter::for('telegram', function () {
            return Limit::perSecond(28);
        });

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['uuid' => $payload['uuid'] ?? \Illuminate\Support\Str::uuid()->toString()];
        });

        Queue::before(function (JobProcessing $event) {
            if (!in_array($event->job->getQueue(), ['telegram'], true)) {
                JobLog::create([
                    'job_id'    => $event->job->uuid(),
                    'name'      => $event->job->resolveName(),
                    'queue'     => $event->job->getQueue(),
                    'payload'   => $event->job->payload(),
                    'attempts'  => $event->job->attempts(),
                    'status'    => 'processing',
                    'started_at' => now(),
                ]);
            }
        });

        Queue::after(function (JobProcessed $event) {
            if (!in_array($event->job->getQueue(), ['telegram'], true)) {
                JobLog::where('job_id', $event->job->uuid())
                    ->update(['status' => 'completed', 'finished_at' => now()]);
            }
        });

        Queue::failing(function (JobFailed $event) {
            if (!in_array($event->job->getQueue(), ['telegram'], true)) {
                JobLog::where('job_id', $event->job->uuid())
                    ->update([
                        'status' => 'failed',
                        'error'  => $event->exception->getMessage(),
                        'finished_at' => now(),
                    ]);
            }
        });
    }
}
