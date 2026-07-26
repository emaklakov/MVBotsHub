<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rules\Password as PasswordRule;
use MoonShine\Laravel\Http\Requests\MoonShineFormRequest;
use MoonShine\Laravel\MoonShineAuth;

final class ResetPasswordRequest extends MoonShineFormRequest
{
    public function authorize(): bool
    {
        return config('moonshine-register.enabled', true)
            && config('moonshine-register.password_reset.enabled', true)
            && MoonShineAuth::getGuard()->guest();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => __('register.email'),
            'password' => __('register.password'),
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'token' => $this->route('token') ?? $this->string('token')->toString(),
            'email' => $this->string('email')->lower()->squish()->toString(),
        ]);
    }
}
