<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        if ($event->guard !== 'moonshine' || ! $event->user) {
            return;
        }

        ActivityLogger::log(
            'logout',
            $event->user,
            'Выход из аккаунта',
            userId: $event->user->getAuthIdentifier(),
        );
    }
}
