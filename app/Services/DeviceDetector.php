<?php

namespace App\Services;

use Jenssegers\Agent\Agent;

class DeviceDetector
{
    public static function detect(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return '—';
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        $platform = $agent->platform() ?: 'Неизвестно';
        $browser = $agent->browser() ?: 'Неизвестно';

        $deviceType = match (true) {
            $agent->isTablet() => 'Планшет',
            $agent->isMobile() => 'Телефон',
            $agent->isDesktop() => 'Компьютер',
            $agent->isRobot() => 'Бот',
            default => 'Неизвестно',
        };

        return "{$deviceType} · {$platform} · {$browser}";
    }
}
