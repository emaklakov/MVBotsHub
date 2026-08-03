<?php

use App\Http\Controllers\Api\Flows\FlowEditorController;
use App\Http\Controllers\Telegram\WebhookController;
use Illuminate\Support\Facades\Route;

// Точка входа для ботов Telegram - Публичный webhook — без auth, проверка через secret token
Route::post('/telegram/webhook/G12wF1nkDNy/{bot:webhook_token}', [WebhookController::class, 'handle'])->name('telegram.webhook');

// API для работы редактора схем
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/bots/{bot}/flows/{flow}/draft', [FlowEditorController::class, 'draft']);
    Route::post('/bots/{bot}/flows/{flow}/save-draft', [FlowEditorController::class, 'saveDraft']);
    Route::post('/bots/{bot}/flows/{flow}/publish', [FlowEditorController::class, 'publish']);
});
