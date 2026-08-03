<?php

use App\Http\Controllers\Api\Flows\FlowEditorController;
use Illuminate\Support\Facades\Route;

Route::prefix('flow-editor')
    ->middleware(['web', 'auth:' . config('moonshine.auth.guard', 'moonshine')])
    ->group(function () {
        Route::get('bots/{bot:id}/flows/{flow}/editor', function ($bot, $flow) {
            $manifest = json_decode(file_get_contents(public_path('build-flow-editor/.vite/manifest.json')), true);

            $js = $manifest['src/main.ts']['file'] ?? 'assets/main.js';
            $css = $manifest['src/main.ts']['css'][0] ?? 'assets/main.css';

            return view('flows.flow-editor', compact('bot', 'flow', 'js', 'css'));
        })->name('flow.editor');

        // API для работы редактора схем
        Route::get('/bots/{bot:id}/flows/{flow}/draft', [FlowEditorController::class, 'draft']);
        Route::post('/bots/{bot:id}/flows/{flow}/save-draft', [FlowEditorController::class, 'saveDraft']);
        Route::post('/bots/{bot:id}/flows/{flow}/publish', [FlowEditorController::class, 'publish']);
    });
