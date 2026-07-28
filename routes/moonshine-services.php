<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FailedJobMassRetryController;

Route::moonshine(static function (Router $router): void {
    $router->post('failed-jobs/mass-retry', FailedJobMassRetryController::class)
        ->middleware('auth:' . moonshineConfig()->getGuard())
        ->name('failed-jobs.mass-retry');
})->prefix('services');
