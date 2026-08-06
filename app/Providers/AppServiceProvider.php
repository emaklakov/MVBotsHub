<?php

namespace App\Providers;

use App\Application\Broadcasts\ProgressTracker;
use App\Application\Broadcasts\Services\BroadcastDispatcher;
use App\Application\Flows\BlockExecutorRegistry;
use App\Application\Flows\Executors\ApiCallBlockExecutor;
use App\Application\Flows\Executors\ButtonBlockExecutor;
use App\Application\Flows\Executors\ConditionBlockExecutor;
use App\Application\Flows\Executors\DelayBlockExecutor;
use App\Application\Flows\Executors\InlineKeyboardBlockExecutor;
use App\Application\Flows\Executors\InputBlockExecutor;
use App\Application\Flows\Executors\JumpBlockExecutor;
use App\Application\Flows\Executors\TextBlockExecutor;
use App\Application\Flows\Services\FlowEngine;
use App\Application\Flows\Services\VariableResolver;
use App\Application\Telegram\MessageRecorder;
use App\Domain\Flows\Contracts\MessengerInterface;
use App\Domain\Flows\Contracts\SessionStoreInterface;
use App\Domain\Flows\Contracts\VariableResolverInterface;
use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\Users\ProfileController;
use App\Infrastructure\Persistence\EloquentSessionStore;
use App\Infrastructure\Telegram\TelegramMessenger;
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

        $this->app->singleton(
            \App\Domain\Bots\Contracts\TelegramGatewayInterface::class,
            \App\Infrastructure\Telegram\TelegraphGateway::class
        );

        $this->app->singleton(ProgressTracker::class);
        $this->app->singleton(BroadcastDispatcher::class);

        $this->app->singleton(SessionStoreInterface::class, EloquentSessionStore::class);
        $this->app->singleton(MessengerInterface::class, TelegramMessenger::class);
        $this->app->singleton(VariableResolverInterface::class, VariableResolver::class);
        $this->app->singleton(MessageRecorder::class);

        $this->app->singleton(BlockExecutorRegistry::class, function ($app) {
            $registry = new BlockExecutorRegistry();
            $registry->register($app->make(TextBlockExecutor::class));
            $registry->register($app->make(ButtonBlockExecutor::class));
            $registry->register($app->make(InputBlockExecutor::class));
            $registry->register($app->make(ConditionBlockExecutor::class));
            $registry->register($app->make(JumpBlockExecutor::class));
            $registry->register($app->make(ApiCallBlockExecutor::class));
            $registry->register($app->make(DelayBlockExecutor::class));
            $registry->register($app->make(InlineKeyboardBlockExecutor::class));
            return $registry;
        });

        $this->app->singleton(FlowEngine::class);
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

        $excludedLogQueues = ['telegram', 'broadcast'];

        Queue::before(function (JobProcessing $event) use ($excludedLogQueues) {
            if (!in_array($event->job->getQueue(), $excludedLogQueues, true)) {
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

        Queue::after(function (JobProcessed $event) use ($excludedLogQueues) {
            if (!in_array($event->job->getQueue(), $excludedLogQueues, true)) {
                JobLog::where('job_id', $event->job->uuid())
                    ->update(['status' => 'completed', 'finished_at' => now()]);
            }
        });

        Queue::failing(function (JobFailed $event) use ($excludedLogQueues) {
            if (!in_array($event->job->getQueue(), $excludedLogQueues, true)) {
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
