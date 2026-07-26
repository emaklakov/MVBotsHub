<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Auth;

use App\MoonShine\Pages\Auth\Concerns\WithAuthPageAssets;
use MoonShine\AssetManager\InlineJs;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Core\Attributes\Layout;
use MoonShine\Laravel\Layouts\LoginLayout;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\FlexibleRender;
use App\Forms\ResetPasswordForm;

/**
 * Класс ResetPasswordPage представляет страницу сброса пароля пользователя.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
#[SkipMenu]
#[Layout(LoginLayout::class)]
final class ResetPasswordPage extends Page
{
    use WithAuthPageAssets;

    protected function booted(): void
    {
        $this->title(__('register.reset_title'));
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        $token = (string) request()->route('token');
        $email = (string) request()->string('email')->toString();

        $components = [];

        if (session()->has('status')) {
            $components[] = FlexibleRender::make(
                '<div class="my-4 rounded-lg border border-red-200 px-4 py-3 text-sm text-base-text bg-red-300">'
                . e((string) session('status'))
                . '</div>'
            );
        }

        $components[] = ResetPasswordForm::make(
            action: route('moonshine.password.update', ['token' => $token]),
            email: $email,
            token: $token
        )();

        $components[] = FlexibleRender::make(
            '<div class="authentication-footer description text-center">'
            . '<div><a href="'
            . e(route('moonshine.login'))
            . '">'
            . e(__('register.login_link'))
            . '</a></div>'
            . '<div><a href="'
            . e(route('moonshine.register'))
            . '">'
            . e(__('register.register_link'))
            . '</a></div>'
            . '</div>'
        );

        return $components;
    }

    protected function modifyLayout(\MoonShine\Contracts\UI\LayoutContract $layout): \MoonShine\Contracts\UI\LayoutContract
    {
        if ($layout instanceof LoginLayout) {
            $layout
                ->title(__('register.reset_title'))
                ->description(
                    __('register.reset_description', [
                        'email' => (string) request()->string('email'),
                    ])
                );
        }

        return $layout;
    }
}
