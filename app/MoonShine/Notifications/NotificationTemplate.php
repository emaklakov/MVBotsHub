<?php

namespace App\MoonShine\Notifications;

use App\Enums\NotificationPriority;
use MoonShine\Crud\Notifications\NotificationButton;

final class NotificationTemplate
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        private readonly string $translationKey,
        private readonly array $params = [],
        private readonly NotificationPriority $priority = NotificationPriority::NORMAL,
        private readonly ?string $category = null,
        private readonly ?NotificationButton $button = null,
        private readonly string $icon = 'information-circle',
        private readonly ?int $expiresInHours = null,
        private readonly ?string $groupKey = null,
    ) {}

    public function message(): string
    {
        return trans($this->translationKey, $this->params);
    }

    public function priority(): NotificationPriority
    {
        return $this->priority;
    }

    public function category(): ?string
    {
        return $this->category;
    }

    public function button(): ?NotificationButton
    {
        return $this->button;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresInHours
            ? now()->addHours($this->expiresInHours)->toDateTimeImmutable()
            : null;
    }

    public function groupKey(): ?string
    {
        return $this->groupKey;
    }

    /**
     * Фабрики для типовых сценариев
     */
    public static function newOrder(int $orderId, float $total): self
    {
        return new self(
            translationKey: 'notifications.order.new',
            params: ['id' => $orderId, 'total' => number_format($total, 2)],
            priority: NotificationPriority::HIGH,
            category: 'orders.new',
            button: new NotificationButton(
                label: trans('notifications.order.open'),
                link: route('moonshine.resource.order.edit', $orderId),
                attributes: ['target' => '_blank'],
            ),
            icon: 'shopping-cart',
            groupKey: 'orders.new',
        );
    }

    public static function userRegistered(int $userId, string $name): self
    {
        return new self(
            translationKey: 'notifications.user.registered',
            params: ['id' => $userId, 'name' => $name],
            priority: NotificationPriority::NORMAL,
            category: 'users.registered',
            icon: 'user-plus',
            groupKey: 'users.registered',
        );
    }

    public static function systemError(string $error): self
    {
        return new self(
            translationKey: 'notifications.system.error',
            params: ['error' => $error],
            priority: NotificationPriority::CRITICAL,
            category: 'system.errors',
            icon: 'bug-ant',
            expiresInHours: 72,
        );
    }
}
