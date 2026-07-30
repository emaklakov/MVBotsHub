<?php

namespace App\Providers;

use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Auth\AuthenticateController;
use App\Models\Admin\User\User;
use App\Models\Job\JobLog;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
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
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['uuid' => $payload['uuid'] ?? \Illuminate\Support\Str::uuid()->toString()];
        });

        Queue::before(function (JobProcessing $event) {
            JobLog::create([
                'job_id'    => $event->job->uuid(),
                'name'      => $event->job->resolveName(),
                'queue'     => $event->job->getQueue(),
                'payload'   => $event->job->payload(),
                'attempts'  => $event->job->attempts(),
                'status'    => 'processing',
                'started_at' => now(),
            ]);
        });

        Queue::after(function (JobProcessed $event) {
            JobLog::where('job_id', $event->job->uuid())
                ->update(['status' => 'completed', 'finished_at' => now()]);
        });

        Queue::failing(function (JobFailed $event) {
            JobLog::where('job_id', $event->job->uuid())
                ->update([
                    'status' => 'failed',
                    'error'  => $event->exception->getMessage(),
                    'finished_at' => now(),
                ]);
        });
    }
}
