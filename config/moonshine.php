<?php

declare(strict_types=1);

use App\Exceptions\MVNotFoundException;
use App\Http\Middleware\CheckExpiredPassword;
use App\Http\Middleware\CheckUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TwoFactorVerified;
use App\Models\User;
use App\MoonShine\ColorManager\Palettes\MVPalette;
use App\MoonShine\Pages\Auth\LoginPage;
use App\MoonShine\Pages\Errors\ErrorPage;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MoonShine\Crud\Forms\FiltersForm;
use MoonShine\Laravel\Exceptions\MoonShineNotFoundException;
use MoonShine\Laravel\Http\Middleware\Authenticate;
use MoonShine\Laravel\Http\Middleware\ChangeLocale;

return [
    'title' => env('MOONSHINE_TITLE', 'MoonShine'),
    'logo' => '/images/logos/logo.png',
    'logo_small' => '/images/logos/logo-small.png',

    'favicons' => [
        'apple-touch' => '/apple-touch-icon.png',
        '32' => '/favicon-32x32.png',
        '16' => '/favicon-16x16.png',
        'safari-pinned-tab' => '/vendor/moonshine/safari-pinned-tab.svg',
    ],

    // Default flags
    'use_migrations' => false,
    'use_notifications' => true,
    'use_database_notifications' => true,
    'use_routes' => true,
    'use_profile' => true,

    // Routing
    'domain' => env('MOONSHINE_DOMAIN'),
    'prefix' => env('MOONSHINE_ROUTE_PREFIX', 'admin'),
    'page_prefix' => env('MOONSHINE_PAGE_PREFIX', 'page'),
    'resource_prefix' => env('MOONSHINE_RESOURCE_PREFIX', 'resource'),
    'home_route' => 'moonshine.index',

    // Error handling
    'not_found_exception' => MVNotFoundException::class,

    // Middleware
    'middleware' => [
        ConvertEmptyStringsToNull::class,
        SecurityHeaders::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
        ChangeLocale::class,
    ],

    // Storage
    'disk' => 'public',
    'disk_options' => [],
    'cache' => 'file',

    // Authentication and profile
    'auth' => [
        'enabled' => true,
        'guard' => 'moonshine',
        'model' => User::class,
        'middleware' => [
            Authenticate::class,
            CheckUserIsActive::class,
            CheckExpiredPassword::class,
            TwoFactorVerified::class,
        ],
        'pipelines' => [],
    ],

    // Authentication and profile
    'user_fields' => [
        'username' => 'email',
        'password' => 'password',
        'name' => 'name',
        'avatar' => 'avatar',
    ],

    // Layout, palette, pages, forms
    'layout' => App\MoonShine\Layouts\MoonShineLayout::class,
    'palette' => MVPalette::class,

    'forms' => [
        'login' => App\Forms\LoginForm::class,
        'filters' => FiltersForm::class,
    ],

    'pages' => [
        'dashboard' => App\MoonShine\Pages\Admin\Dashboard::class,
        'profile' => App\MoonShine\Pages\Admin\ProfilePage::class,
        'login' => LoginPage::class,
        'error' => ErrorPage::class,
    ],

    // Localizations
    'locale' => env('APP_LOCALE', 'en'),
    'locale_key' => ChangeLocale::KEY,
    'locales' => [
        // en
    ],
];
