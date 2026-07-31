<?php

namespace App\Models\Admin\User;

use Illuminate\Notifications\DatabaseNotification;

final class Notification extends DatabaseNotification
{
    protected $table = 'notifications';

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    protected $fillable = []; // read-only для MoonShine
}
