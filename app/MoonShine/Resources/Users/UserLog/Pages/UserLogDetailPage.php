<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Users\UserLog\Pages;

use App\Application\Users\Services\DeviceDetector;
use App\MoonShine\Resources\Base\BaseDetailPage;
use App\MoonShine\Resources\Users\UserLog\UserLogResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;

/**
 * @extends DetailPage<UserLogResource>
 */
class UserLogDetailPage extends BaseDetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Date::make('Дата', 'created_at')->format('d.m.Y H:i:s'),
            Text::make('Пользователь', 'user.name'),
            Text::make('IP', 'ip_address'),
            Text::make('Устройство', 'user_agent')
                ->changePreview(fn (?string $value, Text $field) => DeviceDetector::detect($value)),
            Text::make('User Agent', 'user_agent'),
            Text::make('Описание', 'description'),
            Text::make('Объект', 'subject_type')
                ->changePreview(fn ($value, $field) => $value
                    ? class_basename($value) . ' #' . $field->getData()->subject_id
                    : '—'),
            Text::make('Действие', 'action'),
            Json::make('Изменения', 'changes')
                ->changePreview(function (?array $value) {
                    if (blank($value) || !isset($value['before'], $value['after'])) {
                        return '—';
                    }

                    $formatValue = function ($val) {
                        if (is_null($val)) {
                            return '<span class="text-base-text/40">пусто</span>';
                        }

                        if (is_bool($val)) {
                            return $val ? 'Да' : 'Нет';
                        }

                        if (is_numeric($val) && in_array((int) $val, [0, 1], true) && ! is_float($val)) {
                            return ((int) $val) === 1 ? 'Да' : 'Нет';
                        }

                        if (is_array($val)) {
                            return e(json_encode($val, JSON_UNESCAPED_UNICODE));
                        }

                        $str = (string) $val;

                        // длинные значения (например, HTML/описания) обрезаем для читаемости в таблице
                        return mb_strlen($str) > 80
                            ? e(mb_substr($str, 0, 80)) . '…'
                            : e($str);
                    };

                    $fields = array_unique([
                        ...array_keys($value['before']),
                        ...array_keys($value['after']),
                    ]);

                    $rows = '';

                    foreach ($fields as $field) {
                        $oldValue = $value['before'][$field] ?? null;
                        $newValue = $value['after'][$field] ?? null;

                        $rows .= '<tr>'
                            . '<td class="px-3 py-1 text-sm font-medium">' . e($field) . '</td>'
                            . '<td class="px-3 py-1 text-sm text-red-600">' . $formatValue($oldValue) . '</td>'
                            . '<td class="px-3 py-1 text-sm text-green-600">' . $formatValue($newValue) . '</td>'
                            . '</tr>';
                    }

                    return '<table class="text-left border-collapse">'
                        . '<thead><tr>'
                        . '<th class="px-3 py-1 text-xs text-base-text/60">Поле</th>'
                        . '<th class="px-3 py-1 text-xs text-base-text/60">Было</th>'
                        . '<th class="px-3 py-1 text-xs text-base-text/60">Стало</th>'
                        . '</tr></thead>'
                        . '<tbody>' . $rows . '</tbody>'
                        . '</table>';
                }),
        ];
    }
}
