<?php
// app/Http/Controllers/Admin/NotificationApiController.php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\MoonShine\Contracts\Notifications\EnhancedMoonShineNotificationContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class NotificationApiController extends Controller
{
    public function __construct(
        private readonly EnhancedMoonShineNotificationContract $notification,
    ) {}

    public function unread(): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        return response()->json([
            'count' => $this->notification->countUnreadForUser($user->id),
            'items' => $this->notification->getUnreadForUser($user->id),
        ]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $user->notifications()
            ->where('id', $id)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function markManyAsOpened(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => true]);
        }

        $this->notification->markManyAsOpened($ids, $user->id);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $this->notification->readAll();

        return response()->json(['success' => true]);
    }

    public function markCategoryAsRead(string $category): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $this->notification->readAllByCategory($user->id, $category);

        return response()->json(['success' => true]);
    }

    private function getUser(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return auth(config('moonshine.auth.guard'))->user();
    }
}
