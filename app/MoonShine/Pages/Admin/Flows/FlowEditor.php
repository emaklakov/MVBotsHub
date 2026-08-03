<?php

declare(strict_types=1);

namespace App\MoonShine\Pages\Admin\Flows;

use App\MoonShine\Layouts\MoonShineLayout;
use App\MoonShine\Resources\Telegram\Flows\Flow\FlowResource;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\Js;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Core\Attributes\Layout;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Layout\Div;

#[Layout(MoonShineLayout::class)]
class FlowEditor extends Page
{
    protected $bot_id;
    protected $flow_id;
    protected $js;
    protected $css;

    protected function onLoad(): void
    {
        parent::onLoad();

        $this->getAssetManager()
            ->add(Css::make('build-flow-editor/'.$this->css))
            ->append(Js::make('build-flow-editor/'.$this->js));
    }

    public function setBot($bot_id): Page
    {
        $this->bot_id = $bot_id;

        return $this;
    }

    public function setFlow($flow_id): Page
    {
        $this->flow_id = $flow_id;

        return $this;
    }

    public function setJS($js): Page
    {
        $this->js = $js;

        return $this;
    }

    public function setCSS($css): Page
    {
        $this->css = $css;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            app(FlowResource::class)->getIndexPageUrl() => 'Потоки',
            app(FlowResource::class)->getDetailPageUrl($this->flow_id) => 'Потоки '.$this->flow_id,
            '#' => $this->getTitle(),
        ];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Редактор потока';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
	{
		return [
            Div::make()
                ->customAttributes([
                    'id' => 'flow-editor',
                    'data-bot-id' => $this->bot_id,
                    'data-flow-id' => $this->flow_id,
                ])
                ->style(['height' => '100vh', 'width' => '100vw']),
        ];
	}
}
