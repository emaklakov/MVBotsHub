<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Auth;

use App\MoonShine\Forms\Auth\RegisterForm;
use App\MoonShine\Pages\Auth\Traits\WithAuthPageAssets;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Core\Attributes\Layout;
use MoonShine\Laravel\Layouts\LoginLayout;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\FlexibleRender;

/**
 * Класс RegisterPage представляет страницу регистрации пользователя.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
#[SkipMenu]
#[Layout(LoginLayout::class)]
final class RegisterPage extends Page
{
    use WithAuthPageAssets;

    protected function booted(): void
    {
        $this->title(__('register.title'));
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        $components = [];

        if (session()->has('status')) {
            $components[] = FlexibleRender::make(
                '<div class="my-4 rounded-lg border border-red-200 px-4 py-3 text-sm text-base-text bg-red-300">'
                . e((string) session('status'))
                . '</div>'
            );
        }

        $components[] = RegisterForm::make(
            action: route('moonshine.register.store')
        )();

        $components[] = FlexibleRender::make(
            '<div class="authentication-footer description text-center"><a href="'
            . e(route('moonshine.login'))
            . '">'
            . e(__('register.login_link'))
            . '</a></div>'
        );

        return [
            ...$components,
        ];
    }

    protected function modifyLayout(\MoonShine\Contracts\UI\LayoutContract $layout): \MoonShine\Contracts\UI\LayoutContract
    {
        if ($layout instanceof LoginLayout) {
            $layout
                ->title(__('register.title'))
                ->description(__('register.description'));
        }

        return $layout;
    }
}
