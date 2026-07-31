<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\Models\Admin\User\Notification;
use App\Models\Admin\User\Role;
use App\Models\Admin\User\Session;
use App\Models\Admin\User\User;
use App\Models\Admin\User\UserLog;
use App\Models\Admin\User\UserSetting;
use App\Models\Job\FailedJob;
use App\Models\Job\Job;
use App\Models\Job\JobLog;
use App\MoonShine\ColorManager\Palettes\MVPalette;
use App\MoonShine\Resources\Jobs\FailedJob\FailedJobResource;
use App\MoonShine\Resources\Jobs\Job\JobResource;
use App\MoonShine\Resources\Jobs\JobLog\JobLogResource;
use App\MoonShine\Resources\Users\Notification\NotificationResource;
use App\MoonShine\Resources\Users\Permission\PermissionResource;
use App\MoonShine\Resources\Users\Role\RoleResource;
use App\MoonShine\Resources\Users\Session\SessionResource;
use App\MoonShine\Resources\Users\User\UserResource;
use App\MoonShine\Resources\Users\UserLog\UserLogResource;
use App\MoonShine\Resources\Users\UserSetting\UserSettingResource;
use Illuminate\Support\Facades\Gate;
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
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Breadcrumbs;
use MoonShine\UI\Components\Layout\Burger;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Header;
use MoonShine\UI\Components\Layout\Menu;
use MoonShine\UI\Components\Layout\Sidebar;
use MoonShine\UI\Components\Layout\ThemeSwitcher;
use MoonShine\UI\Components\Layout\TopBar;
use MoonShine\UI\Components\When;
use Spatie\Permission\Models\Permission;

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
//            InlineJs::make(<<<'JS'
//                document.addEventListener('submit', function (event) {
//                    const form = event.target;
//                    if (!(form instanceof HTMLFormElement)) return;
//
//                    const buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
//
//                    buttons.forEach((btn) => {
//                        if (btn.disabled) {
//                            event.preventDefault();
//                            return;
//                        }
//
//                        setTimeout(() => {
//                            btn.disabled = true;
//                            btn.dataset.originalHtml = btn.innerHTML;
//                            btn.innerHTML = btn.dataset.loadingText || 'Отправка...';
//                        }, 0);
//
//                        setTimeout(() => {
//                            if (btn.disabled) {
//                                btn.disabled = false;
//                                if (btn.dataset.originalHtml) {
//                                    btn.innerHTML = btn.dataset.originalHtml;
//                                }
//                            }
//                        }, 8000);
//                    });
//                }, true);
//            JS),
        ];
    }

    protected function menu(): array
    {
        return [
            //...parent::menu(),
            MenuGroup::make('Пользователи', [
                MenuItem::make(UserResource::class,'Пользователи', 'users')
                    ->canSee(fn () => Gate::allows('viewAny', User::class)),
                MenuItem::make(RoleResource::class,'Роли', 'shield-exclamation')
                    ->canSee(fn () => Gate::allows('viewAny', Role::class)),
                MenuItem::make(PermissionResource::class,'Разрешения', 'shield-check')
                    ->canSee(fn () => Gate::allows('viewAny', Permission::class)),
                MenuItem::make(SessionResource::class,'Сессии', 'arrow-right-end-on-rectangle')
                    ->canSee(fn () => Gate::allows('viewAny', Session::class)),
                MenuItem::make(UserLogResource::class, 'Логи действий')
                    ->icon('shoe-prints', path: 'icons')
                    ->canSee(fn () => Gate::allows('viewAny', UserLog::class)),
                MenuItem::make(UserSettingResource::class, 'Настройки пользователей', 'cog-8-tooth')
                    ->canSee(fn () => Gate::allows('viewAny', UserSetting::class)),
                MenuItem::make(NotificationResource::class, 'Уведомления', 'bell-alert')
                    ->canSee(fn () => Gate::allows('viewAny', Notification::class)),
            ], 'user-group'),
            MenuGroup::make('Система', [

            ], 'cpu-chip'),
            MenuGroup::make('Очередь', [
                MenuItem::make(JobResource::class, 'Журнал очереди', 'square-3-stack-3d')
                    ->canSee(fn () => Gate::allows('viewAny', Job::class)),
                MenuItem::make(FailedJobResource::class, 'Задачи с ошибками', 'exclamation-triangle')
                    ->canSee(fn () => Gate::allows('viewAny', FailedJob::class)),
                MenuItem::make(JobLogResource::class, 'Логи очереди', 'rectangle-stack')
                    ->canSee(fn () => Gate::allows('viewAny', JobLog::class)),
            ], 'square-3-stack-3d'),
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
