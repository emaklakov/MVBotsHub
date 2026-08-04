<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\ColorManager\Palettes\MVPalette;
use App\MoonShine\Components\MainMenu;
use Illuminate\Support\Facades\Vite;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use MoonShine\Crud\Components\Fragment;
use MoonShine\Crud\Components\Layout\Locales;
use MoonShine\Crud\Components\Layout\Notifications;
use MoonShine\Laravel\Components\Layout\Profile;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\Laravel\Pages\ProfilePage;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Breadcrumbs;
use MoonShine\UI\Components\Layout\Burger;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Footer;
use MoonShine\UI\Components\Layout\Header;
use MoonShine\UI\Components\Layout\Menu;
use MoonShine\UI\Components\Layout\Sidebar;
use MoonShine\UI\Components\Layout\ThemeSwitcher;
use MoonShine\UI\Components\Layout\TopBar;
use MoonShine\UI\Components\When;

/**
 * Класс MoonShineLayout расширяет AppLayout и предоставляет пользовательский макет для админ-панели.
 * Определяет цветовую палитру, ресурсы, меню и другие элементы макета.
 */
final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = MVPalette::class;
    protected bool $topBar = true;

    protected function assets(): array
    {
        return [
            //...parent::assets(),
            Css::make('/vendor/moonshine/assets/main.css')
                ->customAttributes(['media' => 'print', 'onload' => "this.media='all'"]),
            Js::make('/vendor/moonshine/assets/app.js')->defer(),
            Css::make(Vite::asset('resources/css/app.css')),
            Js::make(Vite::asset('resources/js/app.js')),
        ];
    }

    protected function menu(): array
    {
        return [
            ...MainMenu::menu(),
        ];
    }

    protected function topBarSlot(): array
    {
        return [
            Locales::make()->class('text-xl mt-2 mr-2'),
            When::make(
                fn (): bool => $this->hasThemes() && ! $this->isAlwaysDark(),
                static fn (): array => [ThemeSwitcher::make()->class('text-xl mt-2 mr-2')],
            ),
            When::make(
                fn (): bool => $this->isUseNotifications(),
                static fn (): array => [Notifications::make()->class('text-xl mt-2')],
            ),
            Div::make()->class('menu-divider menu-divider--vertical'),
        ];
    }

    protected function getTopBarComponent(): TopBar
    {
        return TopBar::make([
            Fragment::make([
                $this->getLogoComponent()->minimized(),
            ])
                ->class('menu-logo')
                ->name('topbar-logo'),

            Fragment::make([
                Menu::make()->top(),
            ])->class('menu menu--horizontal')->name('topbar-menu'),

            Fragment::make([
                ...$this->topBarSlot(),
                When::make(
                    fn (): bool => $this->isProfileEnabled(),
                    fn (): array
                    => [
                        $this->getProfileComponent(),
                    ],
                ),
                Div::make()->class('menu-divider menu-divider--vertical'),
                Div::make(array_filter([
                    $this->mobileMode ? null : Burger::make()->topbar()->class('text-3xl!'),
                ]))->class('menu-burger'),
            ])->class('menu-actions')->name('topbar-actions'),
        ])->class('lg:hidden! h-15! shadow-md!');
    }

    protected function sidebarTopSlot(): array
    {
        return [
            ActionButton::make('', url('/admin'))
                ->icon('file-circle-question', path: 'icons')->class('p-2 text-xl text-primary'),
        ];
    }

    protected function getSidebarComponent(): Sidebar
    {
        return Sidebar::make([
            Fragment::make([
                Div::make([
                    $this->getLogoComponent()->minimized(),
                ])->class('menu-logo'),
                Div::make([
                    ...$this->sidebarTopSlot(),
                ])->class('menu-actions'),
                Div::make(array_filter([
                    $this->mobileMode ? null : Burger::make()->sidebar(),
                ]))->class('menu-burger'),
            ])->class('menu-header')->name('sidebar-top'),

            Fragment::make([
                ...$this->sidebarSlot(),
                Menu::make(),
            ])->class('menu menu--vertical')->name('sidebar-content'),
        ])
            ->class('hidden! lg:flex!')
            ->collapsed($this->secondBar === false);
    }

    protected function getHeaderComponent(): Header
    {
        $homeLabel = $this->getCore()->getTranslator()->get('moonshine::ui.home');

        if ($homeLabel === 'moonshine::ui.home') {
            $homeLabel = 'Home';
        }

        return Header::make([
            Div::make()->customAttributes([
                'id' => 'page-loader'
            ]),
            Breadcrumbs::make(
                $this->getPage()->getBreadcrumbs(),
            )->prepend(
                $this->getHomeUrl(),
                label: $homeLabel,
            ),
            $this->getSearchComponent(),
            When::make(
                fn (): bool => $this->hasThemes() && ! $this->isAlwaysDark() && ($this->mobileMode || (! $this->sidebar && ! $this->topBar)),
                static fn (): array => [ThemeSwitcher::make(),],
            ),
            Locales::make()->class('hidden! lg:flex!'),
            When::make(
                fn (): bool => $this->hasThemes() && ! $this->isAlwaysDark(),
                fn (): array
                => [
                    Fragment::make([
                        ThemeSwitcher::make()->class('text-xl mt-2 mr-2'),
                    ])->class('hidden! lg:flex!'),
                ],
            ),
            When::make(
                fn (): bool => $this->isUseNotifications(),
                fn (): array
                => [
                    Fragment::make([
                        Notifications::make(),
                    ])->class('hidden! lg:flex!'),
                ],
            ),
            Div::make()->class('menu-divider menu-divider--vertical hidden! lg:flex!'),
            When::make(
                fn (): bool => $this->isProfileEnabled(),
                fn (): array
                => [
                    Fragment::make([
                        $this->getProfileComponent(),
                    ])->name('profile')->class('hidden! lg:flex!'),
                ],
            ),
        ]);
    }

    protected function getSearchComponent(): \MoonShine\Contracts\UI\ComponentContract
    {
        return Fragment::make(); // ничего не рендерит
    }

    protected function getProfileComponent(): Profile
    {
        return Profile::make()->menu([
            ActionButton::make(
                label: $this->getCore()->getTranslator()->get('moonshine::ui.profile'),
                url: $this->getCore()->getRouter()->getEndpoints()->toPage(
                    $this->getCore()->getConfig()->getPage('profile', ProfilePage::class),
                ),
            )->icon('user'),
        ])
            ->avatarPlaceholder(asset('images/default-avatar.png'));
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);
    }

    protected function getFooterComponent(): Footer
    {
        return Footer::make()
            ->copyright($this->getFooterCopyright())
            ->menu($this->getFooterMenu());
    }

    protected function getFooterCopyright(): string
    {
        return \sprintf(
            <<<'HTML'
                &copy; %d MV
                HTML,
            now()->year,
        );
    }

    protected function getFooterMenu(): array
    {
        return [
            url('/admin') => 'Панель управления',
        ];
    }
}
