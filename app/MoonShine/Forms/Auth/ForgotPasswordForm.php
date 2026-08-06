<?php

declare(strict_types=1);

namespace App\MoonShine\Forms\Auth;

use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FormContract;
use MoonShine\Support\Traits\Makeable;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Email;

final class ForgotPasswordForm implements FormContract
{
    use Makeable;

    public function __construct(
        private readonly string $action,
    ) {
    }

    public function __invoke(): FormBuilderContract
    {
        return FormBuilder::make()
            ->class('authentication-form')
            ->action($this->action)
            ->errorsAbove(false)
            ->fields([
                Email::make(__('register.email'), 'email')
                    ->required()
                    ->customAttributes([
                        'autofocus' => true,
                        'autocomplete' => 'email',
                    ]),
            ])
            ->submit(__('register.reset_submit'), [
                'class' => 'btn-primary btn-lg w-full',
            ]);
    }
}
