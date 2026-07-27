<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FailedJobMassRetryController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::moonshine(static function (Router $router): void {
    $router->post('services/failed-jobs/mass-retry', FailedJobMassRetryController::class)
        ->middleware('auth:' . moonshineConfig()->getGuard())
        ->name('failed-jobs.mass-retry');
});
