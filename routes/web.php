<?php

use App\Http\Controllers\Telegram\WebhookController;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

// Точка входа для ботов Telegram - Публичный webhook — без auth, проверка через secret token
Route::post('/telegram/webhook/G12wF1nkDNy/{bot:webhook_token}', [WebhookController::class, 'handle'])
    ->withoutMiddleware([AuthenticateSession::class])
    ->name('telegram.webhook');

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');
