<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Auth;

use App\MoonShine\Pages\Auth\Concerns\WithAuthPageAssets;
use MoonShine\AssetManager\InlineJs;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Core\Attributes\Layout;
use MoonShine\Laravel\Layouts\LoginLayout;
use MoonShine\Laravel\Pages\LoginPage as MoonShineLoginPage;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\FlexibleRender;

/**
 * Класс LoginPage представляет страницу входа в систему.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
#[SkipMenu]
#[Layout(LoginLayout::class)]
final class LoginPage extends MoonShineLoginPage
{
    use WithAuthPageAssets;

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        $footerLinks = [];

        if (config('moonshine-register.password_reset.enabled', true)) {
            $footerLinks[] = '<div><a href="'
                . e(route('moonshine.password.request'))
                . '" class="btn btn-secondary btn-lg w-full">'
                . e(__('register.forgot_link'))
                . '</a></div>';
        }

        if (config('moonshine-register.login_link.enabled', true)) {
            $footerLinks[] = '<div><a href="'
                . e(route('moonshine.register'))
                . '">'
                . e(__('register.register_link'))
                . '</a></div>';
        }

        $components = [
            ...parent::components(),
        ];

        if (session()->has('status')) {
            $components[] = FlexibleRender::make(
                '<div class="my-4 rounded-lg border border-red-200 px-4 py-3 text-sm text-base-text bg-red-300">'
                . e((string) session('status'))
                . '</div>'
            );
        }

        if ($footerLinks !== []) {
            $components[] = FlexibleRender::make(
                '<div class="authentication-footer description text-center flex flex-col gap-2">'
                . implode('', $footerLinks)
                . '</div>'
            );
        }

        return $components;
    }
}
