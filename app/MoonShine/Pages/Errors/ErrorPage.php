<?php

namespace App\MoonShine\Pages\Errors;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Core\Attributes\Layout;
use MoonShine\Laravel\Layouts\BlankLayout;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\FlexibleRender;

/**
 * Класс ErrorPage представляет страницу ошибки.
 * Определяет заголовок, компоненты и другие параметры страницы.
 */
#[SkipMenu]
#[Layout(BlankLayout::class)]
class ErrorPage extends Page
{
    private int $code = 500;

    private string $message = '';

    private string $message_en = '';

    public function message(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function message_en(string $message_en): static
    {
        $this->message_en = $message_en;

        return $this;
    }

    public function code(int $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        $logo = $this->getAssetManager()->getAsset('/images/logos/logo.png');

        $backUrl = $this->getCore()->getRouter()->getEndpoints()->home();

        if ($resourceUri = $this->getCore()->getRouter()->extractResourceUri()) {
            $backUrl = $this->getCore()->getResources()->findByUri($resourceUri)?->getUrl() ?? $backUrl;
        }

        $code = $this->code;
        $message = $this->message;
        $message_en = $this->message_en;
        $backTitle = $this->getCore()
            ->getTranslator()
            ->get('moonshine::ui.back');

        /** @var view-string $view */
        $view = 'errors.default';

        return [
            FlexibleRender::make(
                static fn (): Factory|View => view($view),
                ['code' => $code, 'message' => $message, 'logo' => $logo, 'backUrl' => $backUrl, 'backTitle' => $backTitle, 'message_en' => $message_en]
            ),
        ];
    }
}
