<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use MoonShine\Laravel\Http\Requests\MoonShineFormRequest;
use MoonShine\Laravel\MoonShineAuth;

final class ForgotPasswordRequest extends MoonShineFormRequest
{
    public function authorize(): bool
    {
        return config('moonshine-register.enabled', true)
            && config('moonshine-register.password_reset.enabled', true)
            && MoonShineAuth::getGuard()->guest();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => __('register.email'),
        ];
    }
}
