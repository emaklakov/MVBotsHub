<?php

namespace App\Forms;

use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FormContract;
use MoonShine\Support\Traits\Makeable;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Text;

final class TwoFactorForm implements FormContract
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
                Text::make(__('two_factor.code'), 'code')
                    ->required()
                    ->customAttributes([
                        'autofocus' => true,
                        'maxlength' => 6,
                        'autocomplete' => 'one-time-code',
                        'class' => 'text-center tracking-widest',
                    ]),
            ])
            ->submit(__('two_factor.submit'), [
                'class' => 'btn-primary btn-lg w-full',
            ]);
    }
}
