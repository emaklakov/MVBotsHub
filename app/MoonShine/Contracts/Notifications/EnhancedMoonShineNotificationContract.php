<?php
// app/MoonShine/Contracts/Notifications/EnhancedMoonShineNotificationContract.php

declare(strict_types=1);

namespace App\MoonShine\Contracts\Notifications;

use App\Models\Users\Enums\NotificationPriority;
use App\MoonShine\Notifications\NotificationTemplate;
use MoonShine\Crud\Contracts\Notifications\MoonShineNotificationContract;
use MoonShine\Crud\Contracts\Notifications\NotificationButtonContract;

/**
 * Не переопределяем методы родителя (notify, markAsRead, getAll, getReadAllRoute),
 * только добавляем новые.
 */
interface EnhancedMoonShineNotificationContract extends MoonShineNotificationContract
{
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
    ): void;

    public function sendToAll(
        string $message,
        ?NotificationButtonContract $button = null,
        string $color = 'purple',
        string $icon = 'information-circle',
        ?NotificationPriority $priority = null,
        ?string $category = null,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $groupKey = null,
    ): void;

    public function sendTemplate(NotificationTemplate $template, array $ids = []): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUnreadForUser(
        int $userId,
        ?string $category = null,
        ?NotificationPriority $priority = null,
    ): array;

    public function countUnreadForUser(int $userId): int;

    public function markAsOpened(string $notificationId, int $userId): void;

    public function readAllByCategory(int $userId, string $category): void;

    public function markManyAsOpened(array $ids, int $userId): void;
}
