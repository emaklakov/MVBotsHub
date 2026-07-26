# MV Bots App

Административная панель на Laravel и MoonShine для управления пользователями и доступами. Приложение предоставляет готовую инфраструктуру аутентификации, двухфакторной проверки, ролевой модели доступа и аудита действий — основу для дальнейшего развития функционала ботов.

## Основные возможности

- **Админ-панель MoonShine** — управление пользователями, ролями, разрешениями и сессиями
- **Регистрация и восстановление пароля** — самостоятельная регистрация с назначением роли по умолчанию
- **Двухфакторная аутентификация (2FA)** — одноразовый 6-значный код по email после входа
- **Ролевая модель доступа (RBAC)** — роли и разрешения через [spatie/laravel-permission](https://github.com/spatie/laravel-permission)
- **Контроль активности пользователей** — неактивные аккаунты блокируются до активации администратором
- **Истечение пароля** — принудительный сброс пароля по истечении срока действия
- **Ограничение сессий** — опциональный запрет одновременного входа с нескольких устройств
- **Журнал активности** — логирование входов, выходов, CRUD-операций и смены пароля
- **HTTP Security Headers** — CSP, HSTS, X-Frame-Options и другие заголовки безопасности
- **Локализация** — интерфейс на русском языке

## Технологический стек

| Компонент | Версия |
|-----------|--------|
| PHP | 8.3+ |
| Laravel | 13 |
| MoonShine | 4 |
| Tailwind CSS | 4 |
| Pest | 4 |
| SQLite / MySQL / PostgreSQL | — |

## Требования

- PHP 8.3 или выше с расширениями: `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- Composer 2
- Node.js 18+ и npm
- [Laravel Herd](https://herd.laravel.com/) (рекомендуется) или другой PHP-сервер

## Быстрый старт

### 1. Клонирование и установка зависимостей

```bash
git clone <repository-url> mv-bots-app
cd mv-bots-app
composer setup
```

Команда `composer setup` выполняет: установку PHP-зависимостей, создание `.env`, генерацию ключа приложения, миграции, установку npm-пакетов и сборку фронтенда.

### 2. Настройка окружения

Скопируйте `.env.example` в `.env` (если ещё не создан) и настройте основные параметры:

```env
APP_NAME="MV Bots"
APP_URL=https://mv-bots-app.test
APP_LOCALE=ru

DB_CONNECTION=sqlite

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Для SQLite база данных создаётся автоматически при первой миграции (`database/database.sqlite`).

### 3. Миграции и начальные данные

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

Seeder `RolePermissionSeeder` создаёт роли:
- `super-admin` — полный доступ ко всем разделам (через `Gate::before`)
- `user` — роль по умолчанию при регистрации

### 4. Создание администратора

```bash
php artisan tinker --execute '
$user = App\Models\User::factory()->create([
    "name" => "Admin",
    "email" => "admin@example.com",
    "is_active" => true,
]);
$user->assignRole("super-admin");
echo "Admin created: admin@example.com\n";
'
```

### 5. Запуск

При использовании Laravel Herd приложение доступно по адресу:

```
https://mv-bots-app.test
```

Корневой URL `/` перенаправляет на `/admin`.

Для локальной разработки с hot-reload:

```bash
composer dev
```

Запускает одновременно: PHP-сервер, очередь, логи (Pail) и Vite.

## Маршруты

| URL | Описание |
|-----|----------|
| `/` | Редирект на `/admin` |
| `/admin` | Панель управления (MoonShine) |
| `/admin/login` | Вход |
| `/admin/register` | Регистрация |
| `/admin/forgot-password` | Запрос сброса пароля |
| `/admin/reset-password/{token}` | Сброс пароля |
| `/admin/2fa` | Подтверждение двухфакторной аутентификации |
| `/up` | Health-check |

Префикс `/admin` настраивается через `MOONSHINE_ROUTE_PREFIX`.

## Конфигурация

### MoonShine

Файл `config/moonshine.php`:

| Переменная | По умолчанию | Описание |
|------------|--------------|----------|
| `MOONSHINE_TITLE` | `MoonShine` | Заголовок панели |
| `MOONSHINE_ROUTE_PREFIX` | `admin` | URL-префикс |
| `MOONSHINE_DOMAIN` | — | Отдельный домен для панели |

### Регистрация

Файл `config/moonshine-register.php`:

| Переменная | По умолчанию | Описание |
|------------|--------------|----------|
| `MOONSHINE_REGISTER_ENABLED` | `true` | Включить регистрацию |
| `MOONSHINE_REGISTER_ROUTE` | `register` | URL маршрута |
| `MOONSHINE_REGISTER_AUTO_LOGIN` | `false` | Автовход после регистрации |
| `MOONSHINE_REGISTER_ROLE_ID` | `user` | Роль по умолчанию |
| `MOONSHINE_REGISTER_PASSWORD_RESET_ENABLED` | `true` | Восстановление пароля |

### Аутентификация

Файл `config/auth.php`:

| Переменная | По умолчанию | Описание |
|------------|--------------|----------|
| `AUTH_PASSWORD_EXPIRY` | `90` | Срок действия пароля в днях |

### Двухфакторная аутентификация

Маршруты 2FA регистрируются при `config('moonshine-two-factor.enabled') === true` (по умолчанию включено). Код отправляется на email, действует 10 минут. В локальном окружении (`APP_ENV=local`) проверка кода пропускается.

### Политика паролей

Минимальные требования (настроены в `AppServiceProvider`):
- 8 символов
- Заглавные и строчные буквы
- Цифры
- Спецсимволы

## Архитектура

```
app/
├── Exceptions/          # Кастомные исключения (MVNotFoundException)
├── Forms/               # Формы MoonShine (логин, регистрация, 2FA, сброс пароля)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/       # Профиль пользователя
│   │   └── Auth/        # Аутентификация, регистрация, 2FA, сброс пароля
│   ├── Middleware/      # 2FA, проверка активности, истечение пароля, security headers
│   └── Requests/        # Form Request классы
├── Listeners/           # События Login/Logout (2FA, аудит)
├── Models/
│   ├── Admin/           # UserLog
│   ├── Concerns/        # HasTwoFactorEmailCode, LogsUserActivity
│   └── ...              # User, Role, Session, UserCode
├── MoonShine/
│   ├── ColorManager/    # MVPalette — фирменная палитра
│   ├── Layouts/         # MoonShineLayout — меню и ассеты
│   ├── Pages/           # Dashboard, Profile, Auth-страницы, ErrorPage
│   └── Resources/       # CRUD: User, Role, Permission, Session
├── Notifications/       # Email-уведомления (2FA-код, сброс пароля)
├── Policies/            # Политики доступа к ресурсам
├── Providers/           # AppServiceProvider, MoonShineServiceProvider
└── Services/            # ActivityLogger, DeviceDetector

config/
├── moonshine.php        # Основная конфигурация MoonShine
├── moonshine-register.php
├── auth.php             # Guard, срок действия пароля
└── permission.php       # Spatie Permission

database/
├── migrations/          # users, sessions, permissions, user_codes, users_logs
└── seeders/             # RolePermissionSeeder
```

## Безопасность

### Middleware-цепочка MoonShine

После успешной аутентификации каждый запрос проходит через:

1. `Authenticate` — проверка авторизации
2. `CheckUserIsActive` — блокировка неактивных пользователей
3. `CheckExpiredPassword` — проверка срока действия пароля
4. `TwoFactorVerified` — проверка прохождения 2FA

### Двухфакторная аутентификация

При входе (`Login` event → `SendTwoFactorCode`):
1. Генерируется 6-значный код, сохраняется в `user_codes` (хешированный)
2. Код отправляется на email пользователя
3. В сессию записывается флаг `needs_2fa`
4. Middleware перенаправляет на `/admin/2fa` до успешной верификации

Ограничения: 5 попыток ввода за 15 минут, повторная отправка — не чаще раза в минуту.

### Мультиустройственный вход

Поле `enabled_multi_device_login` у пользователя:
- `false` (по умолчанию) — при входе завершаются все другие сессии
- `true` — разрешён одновременный вход с нескольких устройств

### Журнал активности

Таблица `users_logs` фиксирует:

| Действие | Описание |
|----------|----------|
| `login` | Вход в систему |
| `logout` | Выход из системы |
| `password_changed` | Смена пароля |
| `created` / `updated` / `deleted` | CRUD-операции над моделями с трейтом `LogsUserActivity` |

Записи содержат IP, User-Agent, описание и diff изменений (для `updated`).

## Роли и разрешения

### Роли

| Роль | Описание |
|------|----------|
| `super-admin` | Полный доступ (обходит все политики через `Gate::before`) |
| `user` | Роль по умолчанию для новых пользователей |

### Разрешения

Политики ресурсов проверяют разрешения в формате `{resource}.{action}`:

| Ресурс | Разрешения |
|--------|------------|
| Пользователи | `users.view`, `users.create`, `users.update`, `users.delete` |
| Роли | `roles.view`, `roles.create`, `roles.update`, `roles.delete` |
| Разрешения | `permissions.view`, `permissions.create`, `permissions.update`, `permissions.delete` |
| Сессии | `sessions.view`, `sessions.create`, `sessions.update`, `sessions.delete` |

Разрешения назначаются через роли или напрямую пользователю в админ-панели.

## Админ-панель

### Раздел «Система»

- **Пользователи** — CRUD, активация, 2FA, мультиустройство, роли и разрешения
- **Роли** — управление ролями и привязка разрешений
- **Разрешения** — управление разрешениями
- **Сессии** — просмотр активных сессий с определением устройства (DeviceDetector)

### Профиль

Страница профиля (`/admin/page/profile`) позволяет изменить имя, аватар и пароль. Активные сессии отображаются с информацией об устройстве.

### Регистрация новых пользователей

1. Пользователь регистрируется через `/admin/register`
2. Создаётся неактивный аккаунт (`is_active = false`) с ролью `user`
3. Администратор активирует пользователя и назначает необходимые роли/разрешения

## Разработка

### Полезные команды

```bash
# Форматирование PHP-кода
vendor/bin/pint --dirty

# Запуск тестов
php artisan test --compact

# Просмотр маршрутов
php artisan route:list --path=admin

# Очистка кеша
php artisan config:clear && php artisan cache:clear
```

### Добавление нового MoonShine-ресурса

```bash
php artisan moonshine:resource BotResource --model=Bot
```

Зарегистрируйте ресурс в `app/Providers/MoonShineServiceProvider.php` и добавьте пункт меню в `app/MoonShine/Layouts/MoonShineLayout.php`.

### Добавление разрешений

1. Создайте разрешения в `RolePermissionSeeder` или через админ-панель
2. Создайте Policy с проверкой `{resource}.{action}`
3. Укажите `protected bool $withPolicy = true` в ресурсе MoonShine

### Фронтенд

Стили: `resources/css/app.css` (Tailwind CSS v4).
Скрипты: `resources/js/app.js`.

```bash
npm run dev    # разработка с hot-reload
npm run build  # production-сборка
```

### Локализация

Файлы переводов:
- `lang/ru.json` — общие строки
- `lang/ru/register.php` — регистрация и сброс пароля
- `lang/ru/two_factor.php` — двухфакторная аутентификация
- `lang/vendor/moonshine/ru/` — переводы MoonShine

## Тестирование

Проект использует [Pest](https://pestphp.com/) для тестирования.

```bash
# Все тесты
php artisan test --compact

# Конкретный тест
php artisan test --compact --filter=ExampleTest
```

## Лицензия

MIT
