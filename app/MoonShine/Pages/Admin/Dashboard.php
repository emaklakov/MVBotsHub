<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Admin;

use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;

#[\MoonShine\MenuManager\Attributes\SkipMenu]

/**
 * Класс Dashboard представляет главную страницу админ-панели.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
class Dashboard extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle()
        ];
    }

    public function getTitle(): string
    {
        //abort(500);

        return $this->title ?: 'Панель управления';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
	{
		return [];
	}
}
