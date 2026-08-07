<?php

declare(strict_types=1);

namespace App\Domain\Bots\Enums;

/**
 * Каталог системных сообщений бота — тех, что отправляются НЕ из FlowVersion
 * (в отличие от текста блоков, который версионируется вместе со схемой потока
 * и живёт в FlowVersion::schema, см. TextBlockExecutor).
 *
 * Каждый ключ имеет fallback() — переводы "из коробки", которые используются,
 * если для бота ещё не создан BotMessageTemplate или в нём нет нужного языка.
 * Бот не должен отправить пустую строку ни при каких обстоятельствах.
 */
enum SystemMessageKey: string
{
    case WELCOME = 'welcome_message';
    case WELCOME_BACK = 'welcome_back_message';
    case NOT_YOUR_CONTACT = 'not_your_contact_message';
    case SESSION_EXPIRED = 'session_expired_message';

    public function label(): string
    {
        return match ($this) {
            self::WELCOME => 'Приветствие после авторизации',
            self::WELCOME_BACK => 'Приветствие при повторном /start',
            self::NOT_YOUR_CONTACT => 'Ошибка: передан чужой контакт',
            self::SESSION_EXPIRED => 'Сессия диалога истекла',
        };
    }

    /**
     * @return array<string, string>
     */
    public function fallback(): array
    {
        return match ($this) {
            self::WELCOME => [
                'basic' => 'Вы успешно авторизованы.',
                'ru' => 'Вы успешно авторизованы.',
                'en' => 'You are now authorized.',
            ],
            self::WELCOME_BACK => [
                'basic' => 'С возвращением!',
                'ru' => 'С возвращением!',
                'en' => 'Welcome back!',
            ],
            self::NOT_YOUR_CONTACT => [
                'basic' => 'Вы поделились не своим номером.',
                'ru' => 'Вы поделились не своим номером.',
                'en' => 'You shared someone else\'s contact.',
            ],
            self::SESSION_EXPIRED => [
                'basic' => 'Сессия истекла. Начните заново командой /start.',
                'ru' => 'Сессия истекла. Начните заново командой /start.',
                'en' => 'Session expired. Start again with /start.',
            ],
        };
    }
}
