<?php

namespace App\MoonShine\Forms\Auth;

use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FormContract;
use MoonShine\Support\Traits\Makeable;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Components\Link;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

class LoginForm implements FormContract
{
    use Makeable;

    public function __construct(
        private readonly string $action,
        private CoreContract $core
    ) {
    }

    public function __invoke(): FormBuilderContract
    {
        return FormBuilder::make()
            ->class('authentication-form')
            ->action($this->action)
            ->errorsAbove(false)
            ->fields([
                Text::make($this->core->getTranslator()->get('moonshine::ui.login.username'), 'username')
                    ->required()
                    ->customAttributes([
                        'autofocus' => true,
                        'autocomplete' => 'off',
                    ]),

                Password::make($this->core->getTranslator()->get('moonshine::ui.login.password'), 'password')
                    ->customAttributes([
                        'autofocus' => false,
                        'autocomplete' => 'off',
                    ])
                    ->required()->eye(),

                Flex::make([
                    Switcher::make($this->core->getTranslator()->get('moonshine::ui.login.remember_me'), 'remember')->class('w-auto!'),
                    Link::make(route('moonshine.password.request'), __('register.forgot_link'))
                        ->class('text-secondary font-medium form-group items-end!')
                ])
                    ->unwrap()
                    ->justifyAlign('between'),
            ])->submit($this->core->getTranslator()->get('moonshine::ui.login.login'), [
                'class' => 'btn-primary btn-lg w-full',
            ]);
    }
}
