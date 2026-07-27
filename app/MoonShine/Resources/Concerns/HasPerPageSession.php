<?php

namespace App\MoonShine\Resources\Concerns;

trait HasPerPageSession
{
    public function perPageSessionKey(): string
    {
        return sprintf('perPage:%s', $this->getUriKey());
    }

    public function getItemsPerPage(): int
    {
        $itemsPerPage = 26;

        return (int) session()->get(
            $this->perPageSessionKey(),
            $itemsPerPage,
        );
    }
}
