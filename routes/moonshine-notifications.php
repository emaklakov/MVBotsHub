<?php
// routes/moonshine-notifications.php

use App\Http\Controllers\Admin\NotificationApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('moonshine-api/notifications')
    ->middleware(['web', 'moonshine'])
    ->group(function () {
        Route::get('/unread', [NotificationApiController::class, 'unread'])
            ->name('moonshine.notifications.unread');
        Route::post('/{id}/read', [NotificationApiController::class, 'markAsRead'])
            ->name('moonshine.notifications.read');
        Route::post('/opened', [NotificationApiController::class, 'markManyAsOpened'])
            ->name('moonshine.notifications.opened');
        Route::post('/read-all', [NotificationApiController::class, 'markAllAsRead'])
            ->name('moonshine.notifications.read-all');
        Route::post('/category/{category}/read', [NotificationApiController::class, 'markCategoryAsRead'])
            ->name('moonshine.notifications.read-category');
    });
