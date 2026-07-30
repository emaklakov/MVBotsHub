<?php

namespace App\MoonShine\Pages\Auth;

use App\Forms\Auth\TwoFactorForm;
use App\MoonShine\Pages\Auth\Concerns\WithAuthPageAssets;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\LayoutContract;
use MoonShine\Core\Attributes\Layout;
use MoonShine\Laravel\Layouts\LoginLayout;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\FlexibleRender;

/**
 * Класс TwoFactorPage представляет страницу двухфакторной аутентификации.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
#[SkipMenu]
#[Layout(LoginLayout::class)]
final class TwoFactorPage extends Page
{
    use WithAuthPageAssets;

    protected function booted(): void
    {
        $this->title(__('two_factor.title'));
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

        $isLocked = $this->isLocked();

        if ($isLocked) {
            $components[] = FlexibleRender::make(
                '<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">'
                . e(__('two_factor.locked', ['minutes' => (int) ceil($this->lockedSeconds() / 60)]))
                . '</div>'
            );
        } else {
            $components[] = TwoFactorForm::make(
                action: route('moonshine.twoFactor.verify')
            )();

            $components[] = FlexibleRender::make(
                '<form method="POST" action="' . e(route('moonshine.twoFactor.resend')) . '" class="mt-3 text-center">'
                . csrf_field()
                . '<button type="submit" class="text-sm text-primary">' . e(__('two_factor.resend')) . '</button>'
                . '</form>'
            );
        }

        $components[] = FlexibleRender::make(
            '<form method="POST" action="' . e(route('moonshine.logout')) . '" class="mt-3 text-center">'
            . csrf_field()
            . '<input type="hidden" name="_method" value="DELETE">'
            . '<button type="submit" class="text-sm text-base-text/60">' . e(__('two_factor.logout')) . '</button>'
            . '</form>'
        );

        return $components;
    }

    protected function modifyLayout(LayoutContract $layout): LayoutContract
    {
        if ($layout instanceof LoginLayout) {
            $layout
                ->title(__('two_factor.title'))
                ->description(__('two_factor.description'));
        }

        return $layout;
    }

    private function throttleKey(): string
    {
        $userId = Auth::guard('moonshine')->id();

        return "2fa-attempts:{$userId}";
    }

    private function isLocked(): bool
    {
        return RateLimiter::tooManyAttempts($this->throttleKey(), 5);
    }

    private function lockedSeconds(): int
    {
        return RateLimiter::availableIn($this->throttleKey());
    }
}
