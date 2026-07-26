<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserLog\Pages;

use App\Services\DeviceDetector;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\UserLog\UserLogResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;
use Throwable;


/**
 * @extends DetailPage<UserLogResource>
 */
class UserLogDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Устройство', 'user_agent')
                ->changePreview(fn (?string $value, Text $field) => DeviceDetector::detect($value)),
            Text::make('User Agent', 'user_agent'),
            Text::make('Описание', 'description'),
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

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyDetailComponent(ComponentContract $component): ComponentContract
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
