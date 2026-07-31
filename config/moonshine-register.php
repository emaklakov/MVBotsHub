<?php

use App\Models\User\User;
use App\MoonShine\Pages\Auth\ForgotPasswordPage;
use App\MoonShine\Pages\Auth\LoginPage;
use App\MoonShine\Pages\Auth\RegisterPage;
use App\MoonShine\Pages\Auth\ResetPasswordPage;

return [
    'enabled' => env('MOONSHINE_REGISTER_ENABLED', true),

    'route' => env('MOONSHINE_REGISTER_ROUTE', 'register'),

    'page' => RegisterPage::class,

    'login_link' => [
        'enabled' => env('MOONSHINE_REGISTER_LOGIN_LINK_ENABLED', true),
        'page' => LoginPage::class,
    ],

    'password_reset' => [
        'enabled' => env('MOONSHINE_REGISTER_PASSWORD_RESET_ENABLED', true),
        'route' => env('MOONSHINE_REGISTER_PASSWORD_RESET_ROUTE', 'forgot-password'),
        'reset_route' => env('MOONSHINE_REGISTER_PASSWORD_RESET_RESET_ROUTE', 'reset-password'),
        'broker' => env('MOONSHINE_REGISTER_PASSWORD_RESET_BROKER', 'users'),
        'user_model' => env('MOONSHINE_REGISTER_USER_MODEL', User::class),
        'request_page' => ForgotPasswordPage::class,
        'reset_page' => ResetPasswordPage::class,
    ],

    'auto_login' => env('MOONSHINE_REGISTER_AUTO_LOGIN', false),

    'default_role' => env('MOONSHINE_REGISTER_ROLE_ID', 'user'),
];
