<?php

namespace App\Http\Controllers\User;


use App\Application\Services\User\ActivityLogger;
use App\Http\Requests\User\ProfileFormRequest;
use App\MoonShine\Pages\Admin\ProfilePage;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Http\Controllers\MoonShineController;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\Support\Enums\ToastType;
use MoonShine\UI\Enums\HtmlMode;
use Symfony\Component\HttpFoundation\Response;

/**
 * Контроллер для управления данными профиля пользователя:
 * обработка запросов на сохранение данных профиля, логирование смены пароля и возврат соответствующих ответов.
 */
class ProfileController extends MoonShineController
{
    /**
     * Обрабатывает сохранение данных профиля пользователя.
     * Сохраняет изменения, логирует смену пароля и возвращает соответствующий ответ.
     */
    public function store(ProfileFormRequest $request): Response
    {
        $page = moonshineConfig()->getPage('profile', ProfilePage::class);
        $form = $page->getForm();

        $user = MoonShineAuth::getGuard()->user() ?? MoonShineAuth::getModel();

        $passwordField = moonshineConfig()->getUserField('password');
        $passwordChanged = false;

        $success = $form->apply(
            function (Model $item) use (&$passwordChanged, $passwordField): Model {
                $passwordChanged = $passwordField && $item->isDirty($passwordField);

                $item->save();

                return $item;
            },
        );

        $user->refresh();

        if ($success && $passwordChanged) {
            ActivityLogger::log(
                action: 'password_changed',
                subject: $user,
                description: 'Пароль изменён в профиле',
                userId: $user->getAuthIdentifier(),
            );
        }

        $message = $success ? __('moonshine::ui.saved') : __('moonshine::ui.saved_error');
        $type = $success ? ToastType::SUCCESS : ToastType::ERROR;

        if ($request->ajax()) {
            $data = [];

            $form
                ->getFields()
                ->onlyFields()
                ->fillCloned($user->toArray())
                ->refreshFields()
                ->each(function (FieldContract $field) use (&$data): void {
                    $data['htmlData'][] = [
                        'html' => (string) $field
                            ->resolveRefreshAfterApply()
                            ->render(),
                        'selector' => ".profile-form [data-field-selector='{$field->getNameDot()}']",
                        'htmlMode' => HtmlMode::OUTER_HTML->value,
                    ];
                });

            return $this
                ->json(message: $message, data: $data, messageType: $type)
                ->events([
                    AlpineJs::event(
                        JsEvent::FRAGMENT_UPDATED,
                        'profile',
                    ),
                    AlpineJs::event(
                        JsEvent::FRAGMENT_UPDATED,
                        'topbar-actions',
                    ),
                ]);
        }

        $this->toast($message, $type);

        return back();
    }
}
