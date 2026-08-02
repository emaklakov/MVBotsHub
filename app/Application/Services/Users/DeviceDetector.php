<?php

namespace App\Application\Services\Users;

use Jenssegers\Agent\Agent;

/**
 * Сервис для определения типа устройства пользователя:
 * определяет тип устройства пользователя на основе User-Agent строки.
 */
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
