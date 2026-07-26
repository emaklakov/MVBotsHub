<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use MoonShine\Laravel\Http\Requests\MoonShineFormRequest;
use MoonShine\Laravel\MoonShineAuth;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ProfileFormRequest extends MoonShineFormRequest
{
    public function authorize(): bool
    {
        return MoonShineAuth::getGuard()->check();
    }

    public function rules(): array
    {
        $name = moonshineConfig()->getUserField('name');
        $username = moonshineConfig()->getUserField('username');
        $avatar = moonshineConfig()->getUserField('avatar');
        $password = moonshineConfig()->getUserField('password');

        return array_filter([
            $name => blank($name) ? null : ['required'],
//            $username => blank($username) ? null : [
//                'required',
//                Rule::unique(
//                    MoonShineAuth::getModel()::class,
//                    moonshineConfig()->getUserField('username')
//                )->ignore(MoonShineAuth::getGuard()->id()),
//            ],
            $avatar => blank($avatar) ? null : ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,gif'],
            $password => blank($password) ? null : ['sometimes', 'nullable', PasswordRule::defaults(), 'required_with:password_repeat', 'same:password_repeat'],
        ]);
    }
}
