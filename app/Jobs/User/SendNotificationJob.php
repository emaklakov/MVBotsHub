<?php
// app/Jobs/SendMoonShineNotificationJob.php

declare(strict_types=1);

namespace App\Jobs\User;

use App\Models\User\Enums\NotificationPriority;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MoonShine\Crud\Notifications\NotificationButton;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 180;

    public function __construct(
        private readonly string $message,
        private readonly array $userIds,
        private readonly ?NotificationButton $button = null,
        private readonly string $color = 'purple',
        private readonly string $icon = 'information-circle',
        private readonly NotificationPriority $priority = NotificationPriority::NORMAL,
        private readonly ?string $category = null,
        private readonly ?\DateTimeImmutable $expiresAt = null,
        private readonly ?string $groupKey = null,
        private readonly int $chunkSize = 500,
    ) {
        $this->onQueue('notifications'); // ← ЗДЕСЬ
    }

    public function handle(): void
    {
        $notifiableType = config('moonshine.auth.model', \App\Models\User\User::class);
        $expires = $this->expiresAt ? $this->expiresAt->format('Y-m-d H:i:s') : null;

        if ($this->groupKey) {
            $this->handleGrouped($notifiableType, $expires);
            return;
        }

        foreach (array_chunk($this->userIds, $this->chunkSize) as $chunk) {
            $rows = array_map(fn (int $userId): array => [
                'id' => (string) Str::uuid(), // ← UUID
                'type' => DatabaseNotification::class,
                'notifiable_type' => $notifiableType,
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'message' => $this->message,
                    'button' => $this->button?->toArray(),
                    'color' => $this->color,
                    'icon' => $this->icon,
                ]),
                'priority' => $this->priority->value,
                'category' => $this->category,
                'expires_at' => $expires,
                'group_key' => null,
                'group_count' => 1,
                'read_at' => null,
                'opened_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], $chunk);

            DB::table('notifications')->insert($rows);
        }
    }

    private function handleGrouped(string $notifiableType, ?string $expires): void
    {
        foreach ($this->userIds as $userId) {
            $existing = DB::table('notifications')
                ->where('notifiable_type', $notifiableType)
                ->where('notifiable_id', $userId)
                ->where('type', DatabaseNotification::class)
                ->where('group_key', $this->groupKey)
                ->whereNull('read_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($existing) {
                DB::table('notifications')
                    ->where('id', $existing->id)
                    ->update([
                        'group_count' => $existing->group_count + 1,
                        'data' => json_encode([
                            'message' => $this->message,
                            'button' => $this->button?->toArray(),
                            'color' => $this->color,
                            'icon' => $this->icon,
                        ]),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(), // ← UUID
                    'type' => DatabaseNotification::class,
                    'notifiable_type' => $notifiableType,
                    'notifiable_id' => $userId,
                    'data' => json_encode([
                        'message' => $this->message,
                        'button' => $this->button?->toArray(),
                        'color' => $this->color,
                        'icon' => $this->icon,
                    ]),
                    'priority' => $this->priority->value,
                    'category' => $this->category,
                    'expires_at' => $expires,
                    'group_key' => $this->groupKey,
                    'group_count' => 1,
                    'read_at' => null,
                    'opened_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
