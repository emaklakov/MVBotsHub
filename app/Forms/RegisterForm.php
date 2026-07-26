<?php

declare(strict_types=1);

namespace App\Forms;

use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FormContract;
use MoonShine\Support\Traits\Makeable;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\PasswordRepeat;
use MoonShine\UI\Fields\Text;

final class RegisterForm implements FormContract
{
    use Makeable;

    public function __construct(
        private readonly string $action,
    ) {
    }

    public function __invoke(): FormBuilderContract
    {
        $fields = [
            Text::make(__('register.name'), 'name')
                ->required()
                ->customAttributes([
                    'autofocus' => true,
                    'autocomplete' => 'name',
                ]),

            Email::make(__('register.email'), 'username')
                ->required()
                ->customAttributes([
                    'autocomplete' => 'username',
                ]),

            Password::make(__('register.password'), 'password')
                ->required()
                ->customAttributes([
                    'autocomplete' => 'new-password',
                ])->eye(),

            FlexibleRender::make('<p class="text-xs text-gray-500 mt-1">Минимум 8 символов: заглавные и строчные буквы, цифры и спецсимвол.</p>'),

            PasswordRepeat::make(__('register.password_confirmation'), 'password_confirmation')
                ->required()
                ->customAttributes([
                    'autocomplete' => 'new-password',
                ])->eye(),
        ];

        return FormBuilder::make()
            ->class('authentication-form')
            ->action($this->action)
            ->errorsAbove(false)
            ->fields($fields)
            ->submit(__('register.submit'), [
                'class' => 'btn-primary btn-lg w-full',
            ]);
    }
}
