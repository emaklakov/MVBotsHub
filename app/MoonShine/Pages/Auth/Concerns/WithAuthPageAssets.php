<?php

namespace App\MoonShine\Pages\Auth\Concerns;

use Illuminate\Support\Facades\Vite;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\InlineJs;

trait WithAuthPageAssets
{
    protected function onLoad(): void
    {
        parent::onLoad();

        $this->getAssetManager()->append(
            Css::make(Vite::asset('resources/css/app.css'))
        );

        $this->getAssetManager()->append(
            InlineJs::make(<<<'JS'
                document.addEventListener('submit', function (event) {
                    const form = event.target;
                    if (!(form instanceof HTMLFormElement)) return;

                    const buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');

                    buttons.forEach((btn) => {
                        if (btn.disabled) {
                            event.preventDefault();
                            return;
                        }

                        setTimeout(() => {
                            btn.disabled = true;
                            btn.dataset.originalHtml = btn.innerHTML;
                            btn.innerHTML = btn.dataset.loadingText || 'Отправка...';
                        }, 0);

                        setTimeout(() => {
                            if (btn.disabled) {
                                btn.disabled = false;
                                if (btn.dataset.originalHtml) {
                                    btn.innerHTML = btn.dataset.originalHtml;
                                }
                            }
                        }, 8000);
                    });
                }, true);
            JS)
        );
    }
}
