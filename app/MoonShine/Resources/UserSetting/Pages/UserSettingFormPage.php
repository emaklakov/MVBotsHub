<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserSetting\Pages;

use App\Models\Admin\User\UserSetting;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\UserSetting\UserSettingResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Text;
use Throwable;

/**
 * @extends FormPage<UserSettingResource>
 */
class UserSettingFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        $item = $this->getResource()->getItem();

        // Показываем в форме ВСЕГДА расшифрованное значение (plaintext),
        // никогда не показываем и не отправляем ciphertext или маску.
        if ($item instanceof UserSetting && $item->encrypted && filled($item->value)) {
            $item->value = $this->safeDecrypt($item->value) ?? $item->value;
        }

        return [
            Box::make([
                ID::make(),
                BelongsTo::make('Пользователь', 'user', resource: UserResource::class, formatted: 'email')
                    ->nullable(),
                Text::make('Название', 'name'),
                Text::make('Ключ', 'key'),
                Checkbox::make('Зашифровано', 'encrypted'),

                Text::make('Значение', 'value')
                    ->onApply(function (mixed $data, mixed $value, FieldContract $field): mixed {
                        if ($data->encrypted && filled($value)) {
                            $data->value = Crypt::encryptString($value);
                        } else {
                            $data->value = $value;
                        }

                        return $data;
                    }),
            ]),
        ];
    }

    protected function safeDecrypt(string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons();
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }

    /**
     * @param  FormBuilder  $component
     *
     * @return FormBuilder
     */
    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
