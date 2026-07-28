<?php
// app/MoonShine/Notifications/QueuedMoonShineNotification.php

declare(strict_types=1);

namespace App\MoonShine\Notifications;

use App\Enums\NotificationPriority;
use App\Jobs\SendMoonShineNotificationJob;
use App\MoonShine\Contracts\Notifications\EnhancedMoonShineNotificationContract;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use MoonShine\Crud\Contracts\Notifications\NotificationButtonContract;
use MoonShine\Crud\Notifications\NotificationButton;
use MoonShine\Support\Enums\Color;
use Illuminate\Support\Str;

final class QueuedMoonShineNotification implements EnhancedMoonShineNotificationContract
{
    private const SYNC_THRESHOLD = 1;

    /**
     * ОБЯЗАТЕЛЬНЫЙ метод из MoonShineNotificationContract
     */
    public function getAll(): Collection
    {
        $user = auth(config('moonshine.auth.guard'))->user();
        if ($user === null) {
            return collect();
        }

        // ВАЖНО: используем Eloquent отношение, а не DB::table()
        // Тогда $notification->data будет массивом (через casts модели)
        return $user->notifications()
            ->where('type', DatabaseNotification::class)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    /**
     * ОБЯЗАТЕЛЬНЫЙ метод из MoonShineNotificationContract
     */
    public function getReadAllRoute(): string
    {
        return route('notifications.read-all');
    }

    /**
     * ОБЯЗАТЕЛЬНЫЙ метод из MoonShineNotificationContract
     */
    public function markAsRead(int|string $id): void
    {
        $user = auth(config('moonshine.auth.guard'))->user();
        if ($user === null) {
            return;
        }

        $user->notifications()
            ->where('id', $id)
            ->where('type', DatabaseNotification::class)
            ->update(['read_at' => now()]);
    }

    /**
     * ОБЯЗАТЕЛЬНЫЙ метод из MoonShineNotificationContract
     */
    public function notify(
        string $message,
        ?NotificationButtonContract $button = null,
        array $ids = [],
        Color|null|string $color = null,
        ?string $icon = null,
    ): void {
        $colorValue = $color instanceof Color ? $color->value : ($color ?? 'purple');
        $iconValue = $icon ?? 'information-circle';

        $this->sendToMany(
            message: $message,
            ids: $ids,
            button: $button instanceof NotificationButton ? $button : null,
            color: is_string($colorValue) ? $colorValue : 'purple',
            icon: $iconValue,
        );
    }

    public function sendToMany(
        string $message,
        array $ids = [],
        ?NotificationButtonContract $button = null,
        string $color = 'purple',
        string $icon = 'information-circle',
        ?NotificationPriority $priority = null,
        ?string $category = null,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $groupKey = null,
    ): void {
        $priority ??= NotificationPriority::NORMAL;

        if (empty($ids)) {
            $ids = $this->getAllAdminIds();
        }

        $buttonImpl = $button instanceof NotificationButton ? $button : null;

        if (count($ids) < self::SYNC_THRESHOLD) {
            $this->insertBatch(
                ids: $ids,
                message: $message,
                button: $buttonImpl,
                color: $color,
                icon: $icon,
                priority: $priority,
                category: $category,
                expiresAt: $expiresAt,
                groupKey: $groupKey,
            );
            return;
        }

        SendMoonShineNotificationJob::dispatch(
            message: $message,
            userIds: $ids,
            button: $buttonImpl,
            color: $color,
            icon: $icon,
            priority: $priority,
            category: $category,
            expiresAt: $expiresAt,
            groupKey: $groupKey,
        )->onQueue('notifications');
    }

    public function sendToAll(
        string $message,
        ?NotificationButtonContract $button = null,
        string $color = 'purple',
        string $icon = 'information-circle',
        ?NotificationPriority $priority = null,
        ?string $category = null,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $groupKey = null,
    ): void {
        $ids = $this->getAllAdminIds();
        $this->sendToMany(
            message: $message,
            ids: $ids,
            button: $button,
            color: $color,
            icon: $icon,
            priority: $priority,
            category: $category,
            expiresAt: $expiresAt,
            groupKey: $groupKey,
        );
    }

    public function sendTemplate(NotificationTemplate $template, array $ids = []): void
    {
        $this->sendToMany(
            message: $template->message(),
            ids: $ids,
            button: $template->button(),
            color: $template->priority()->color(),
            icon: $template->icon(),
            priority: $template->priority(),
            category: $template->category(),
            expiresAt: $template->expiresAt(),
            groupKey: $template->groupKey(),
        );
    }

    public function readAll(): void
    {
        $user = auth(config('moonshine.auth.guard'))->user();
        if ($user === null) {
            return;
        }

        $user->unreadNotifications()
            ->where('type', DatabaseNotification::class)
            ->update(['read_at' => now()]);
    }

    public function readAllByCategory(int $userId, string $category): void
    {
        DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', config('moonshine.auth.model'))
            ->where('type', DatabaseNotification::class)
            ->where('category', $category)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAsOpened(string $notificationId, int $userId): void
    {
        DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $userId)
            ->where('type', DatabaseNotification::class)
            ->update(['opened_at' => now()]);
    }

    // Добавьте этот метод в класс:

    public function markManyAsOpened(array $ids, int $userId): void
    {
        if (empty($ids)) {
            return;
        }

        DB::table('notifications')
            ->whereIn('id', $ids)
            ->where('notifiable_id', $userId)
            ->where('type', DatabaseNotification::class)
            ->whereNull('opened_at')
            ->update(['opened_at' => now()]);
    }

    public function getUnreadForUser(
        int $userId,
        ?string $category = null,
        ?NotificationPriority $priority = null,
    ): array {
        $query = DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', config('moonshine.auth.model'))
            ->where('type', DatabaseNotification::class)
            ->whereNull('read_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($category) {
            $query->where('category', $category);
        }

        if ($priority) {
            $query->where('priority', $priority->value);
        }

        $rows = $query->orderByRaw("
        CASE priority
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'normal' THEN 3
            WHEN 'low' THEN 4
            ELSE 5
        END
    ")->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return $rows->map(fn ($n) => [
            'id' => $n->id,
            'message' => json_decode($n->data, true)['message'] ?? '',
            'button' => json_decode($n->data, true)['button'] ?? null,
            'color' => json_decode($n->data, true)['color'] ?? 'purple',
            'icon' => json_decode($n->data, true)['icon'] ?? 'information-circle',
            'priority' => $n->priority,
            'category' => $n->category,
            'group_key' => $n->group_key,
            'group_count' => (int) $n->group_count,
            'created_at' => $n->created_at,
        ])->toArray();
    }

    public function countUnreadForUser(int $userId): int
    {
        return DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', config('moonshine.auth.model'))
            ->where('type', DatabaseNotification::class)
            ->whereNull('read_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    private function getAllAdminIds(): array
    {
        $model = config('moonshine.auth.model');
        return $model::query()->pluck('id')->toArray();
    }

    private function insertBatch(
        array $ids,
        string $message,
        ?NotificationButton $button,
        string $color,
        string $icon,
        NotificationPriority $priority,
        ?string $category,
        ?\DateTimeImmutable $expiresAt,
        ?string $groupKey,
    ): void {
        $notifiableType = config('moonshine.auth.model');
        $expires = $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null;

        foreach (array_chunk($ids, 100) as $chunk) {
            $rows = array_map(fn (int $id): array => [
                'id' => (string) Str::uuid(), // ← ГЕНЕРИРУЕМ UUID
                'type' => DatabaseNotification::class,
                'notifiable_type' => $notifiableType,
                'notifiable_id' => $id,
                'data' => json_encode([
                    'message' => $message,
                    'button' => $button?->toArray(),
                    'color' => $color,
                    'icon' => $icon,
                ]),
                'priority' => $priority->value,
                'category' => $category,
                'expires_at' => $expires,
                'group_key' => $groupKey,
                'group_count' => 1,
                'read_at' => null,
                'opened_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], $chunk);

            DB::table('notifications')->insert($rows);
        }
    }
}
