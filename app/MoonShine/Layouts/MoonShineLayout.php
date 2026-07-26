<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\Models\Admin\UserLog;
use App\Models\Session;
use App\MoonShine\ColorManager\Palettes\MVPalette;
use App\MoonShine\Resources\Permission\PermissionResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use App\MoonShine\Resources\Role\RoleResource;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use App\MoonShine\Resources\User\UserResource;
use Spatie\Permission\Models\Permission;
use MoonShine\AssetManager\InlineJs;
use App\MoonShine\Resources\Session\SessionResource;
use App\MoonShine\Resources\UserLog\UserLogResource;

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
            MenuGroup::make('Система', [
                MenuItem::make(UserResource::class,'Пользователи', 'users')
                    ->canSee(fn () => Gate::allows('viewAny', \App\Models\User::class)),
                MenuItem::make(RoleResource::class,'Роли', 'shield-exclamation')
                    ->canSee(fn () => Gate::allows('viewAny', \App\Models\Role::class)),
                MenuItem::make(PermissionResource::class,'Разрешения', 'shield-check')
                    ->canSee(fn () => Gate::allows('viewAny', Permission::class)),
                MenuItem::make(SessionResource::class,'Сессии', 'arrow-right-end-on-rectangle')
                    ->canSee(fn () => Gate::allows('viewAny', Session::class)),
                MenuItem::make(UserLogResource::class, 'Логи действий', 'cursor-arrow-rays')
                    ->canSee(fn () => Gate::allows('viewAny', UserLog::class)),
            ], 'cpu-chip'),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
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
