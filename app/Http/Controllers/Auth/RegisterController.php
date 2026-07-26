<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterFormRequest;
use App\MoonShine\Pages\Auth\RegisterPage;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use MoonShine\Laravel\MoonShineAuth;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    public function create(): Renderable|Response|string
    {
        abort_unless(config('moonshine-register.enabled', true), 404);

        if (MoonShineAuth::getGuard()->check()) {
            return redirect()->route(moonshineConfig()->getHomeRoute());
        }

        $pageClass = config('moonshine-register.page', RegisterPage::class);
        $page = moonshine()->getContainer($pageClass);

        if ($page->isResponseModified()) {
            return $page->getModifiedResponse();
        }

        return $page->render();
    }

    public function store(RegisterFormRequest $request): RedirectResponse
    {
        abort_unless(config('moonshine-register.enabled', true), 404);

        $user = MoonShineAuth::getModel();

        $usernameField = moonshineConfig()->getUserField('username', 'email');
        $passwordField = moonshineConfig()->getUserField('password', 'password');
        $nameField = moonshineConfig()->getUserField('name', 'name');

        $user->{$usernameField} = $request->string('username')->toString();
        $user->{$passwordField} = Hash::make($request->string('password')->toString());

        if ($nameField !== false) {
            $user->{$nameField} = $request->string('name')->toString();
        }

        $user->password_changed_at = Carbon::now()->toDateTimeString();

        $user->save();

        $defaultRole = config('moonshine-register.default_role');

        if ($defaultRole) {
            $user->assignRole($defaultRole);
        }

        if (config('moonshine-register.auto_login', false)) {
            MoonShineAuth::getGuard()->login($user);

            return redirect()->route(moonshineConfig()->getHomeRoute());
        }

        return redirect()
            ->route('moonshine.login')
            ->with('status', __('register.created'));
    }
}
