<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use MoonShine\Laravel\Http\Requests\MoonShineFormRequest;
use MoonShine\Laravel\MoonShineAuth;

final class RegisterFormRequest extends MoonShineFormRequest
{
    public function authorize(): bool
    {
        return config('moonshine-register.enabled', true)
            && MoonShineAuth::getGuard()->guest();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $model = MoonShineAuth::getModel();
        $usernameField = moonshineConfig()->getUserField('username', 'email');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'email',
                'max:190',
                Rule::unique($this->tableFor($model), $usernameField),
            ],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('register.name'),
            'username' => __('register.email'),
            'password' => __('register.password'),
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'name' => $this->string('name')->squish()->toString(),
            'username' => $this->string('username')
                ->when(
                    moonshineConfig()->getUserField('username', 'email') === 'email',
                    static fn (Stringable $str): Stringable => $str->lower()
                )
                ->squish()
                ->toString(),
        ]);
    }

    private function tableFor(Model $model): string
    {
        $connection = $model->getConnectionName();
        $table = $model->getTable();

        return filled($connection) ? "{$connection}.{$table}" : $table;
    }
}
