<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\UserSetting\Pages;

use App\Models\User\UserSetting;
use App\MoonShine\Resources\Base\BaseFormPage;
use App\MoonShine\Resources\Users\User\UserResource;
use App\MoonShine\Resources\Users\UserSetting\UserSettingResource;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends FormPage<UserSettingResource>
 */
class UserSettingFormPage extends BaseFormPage
{
    /**
     * @return FieldContract
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
}
