<?php

use App\Http\Controllers\Users\NotificationApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/notifications')
    ->middleware(['web', 'auth:' . config('moonshine.auth.guard', 'moonshine')])
    ->group(function () {
        Route::get('/unread', [NotificationApiController::class, 'unread'])
            ->name('notifications.unread');
        Route::post('/{id}/read', [NotificationApiController::class, 'markAsRead'])
            ->name('notifications.read');
        Route::post('/opened', [NotificationApiController::class, 'markManyAsOpened'])
            ->name('notifications.opened');
        Route::post('/read-all', [NotificationApiController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
    });
