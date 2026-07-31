<?php

namespace App\MoonShine\Pages\Admin;

use App\Application\Services\User\DeviceDetector;
use App\MoonShine\Resources\Users\Session\SessionResource;
use MoonShine\AssetManager\InlineJs;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Crud\Traits\WithComponentsPusher;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Tabs;
use MoonShine\UI\Components\Tabs\Tab;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\PasswordRepeat;
use MoonShine\UI\Fields\Text;

/**
 * Класс ProfilePage представляет страницу профиля пользователя в админ-панели.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
#[SkipMenu]
class ProfilePage extends Page
{
    use WithComponentsPusher;

    protected function onLoad(): void
    {
        parent::onLoad();

        $this->getAssetManager()->append(
            InlineJs::make(<<<'JS'
            window.addEventListener('fragment_updated:profile', function () {
                const form = document.querySelector('.profile-form');
                if (!form) return;

                const passwordInputs = form.querySelectorAll('input[type="password"]');
                passwordInputs.forEach((input) => {
                    input.value = '';
                });
            });
        JS)
        );
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    public function getTitle(): string
    {
        return __('moonshine::ui.profile');
    }

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        $userFields = array_filter([
            ID::make(),

            moonshineConfig()->getUserField('name')
                ? Text::make(__('moonshine::ui.resource.name'), moonshineConfig()->getUserField('name'))
                ->required()
                : null,

            moonshineConfig()->getUserField('username')
                ? Text::make(__('moonshine::ui.login.username'), moonshineConfig()->getUserField('username'))
                ->disabled()
                : null,

            moonshineConfig()->getUserField('avatar')
                ? Image::make(__('moonshine::ui.resource.avatar'), moonshineConfig()->getUserField('avatar'))
                ->disk(moonshineConfig()->getDisk())
                ->options(moonshineConfig()->getDiskOptions())
                ->dir(moonshineConfig()->getUserAvatarsDir())
                ->removable()
                ->allowedExtensions(['jpg', 'png', 'jpeg', 'gif'])
                : null,
        ]);

        $userPasswordsFields = moonshineConfig()->getUserField('password') ? [
            Heading::make(__('moonshine::ui.resource.change_password')),

            Password::make(__('moonshine::ui.resource.password'), moonshineConfig()->getUserField('password'))
                ->customAttributes(['autocomplete' => 'new-password'])
                ->eye(),

            PasswordRepeat::make(__('moonshine::ui.resource.repeat_password'), 'password_repeat')
                ->customAttributes(['autocomplete' => 'confirm-password'])
                ->eye(),
        ] : [];

        $userAgent = request()->userAgent();
        $userIP = request()->ip();

        $userSessionsFields = [
            HasMany::make( 'Сессии', 'sessions', resource: SessionResource::class)
                ->fields([
                    ID::make(),
                    Text::make('Сессия', 'is_current')
                        ->changePreview(function ($value, $field) use ($userAgent, $userIP) {
                            $item = $field->getData();

                            return $item->user_agent === $userAgent && $item->ip_address === $userIP
                                ? '<span class="text-green-600 font-medium">Текущая сессия</span>'
                                : '';
                        })
                        ->unescape(),
                    Text::make('IP', 'ip_address'),
                    Text::make('Устройство', 'user_agent')
                        ->changePreview(fn (?string $value, Text $field) => DeviceDetector::detect($value)),
                    Text::make('User Agent', 'user_agent'),
                    Date::make('Последняя активность', 'last_activity')
                        ->format('d.m.Y H:i:s'),
                ])->disableOutside()
        ];

        return [
            Box::make([
                Tabs::make([
                    Tab::make(__('moonshine::ui.resource.main_information'), $userFields),
                    Tab::make(__('moonshine::ui.resource.password'), $userPasswordsFields)->canSee(
                        fn (): bool => $userPasswordsFields !== [],
                    ),
                    Tab::make('Сессии', $userSessionsFields),
                ]),
            ]),
        ];
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        return [
            $this->getForm(),
            ...$this->getPushedComponents(),
        ];
    }

    public function getForm(): FormBuilderContract
    {
        $user = MoonShineAuth::getGuard()->user() ?? MoonShineAuth::getModel();

        return FormBuilder::make(
            $this->getRouter()->to('profile.store')
        )
            ->async()
            ->name('profile-form')
            ->class('profile-form')
            ->fields($this->fields())
            ->fillCast($user, new ModelCaster($user::class))
            ->submit(__('moonshine::ui.save'), [
                'class' => 'btn-primary btn-lg',
            ]);
    }
}
