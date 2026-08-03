<?php

use App\Http\Controllers\Jobs\FailedJobMassRetryController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::moonshine(static function (Router $router): void {
    $router->post('failed-jobs/mass-retry', FailedJobMassRetryController::class)->name('failed-jobs.mass-retry');
})->middleware('auth:' . moonshineConfig()->getGuard())->prefix('services');
