<?php

use App\Http\Controllers\Jobs\FailedJobMassRetryController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::moonshine(static function (Router $router): void {
    $router->post('failed-jobs/mass-retry', FailedJobMassRetryController::class)
        ->middleware('auth:' . moonshineConfig()->getGuard())
        ->name('failed-jobs.mass-retry');
})->prefix('services');
