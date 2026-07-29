<?php

namespace App\MoonShine\Resources\Concerns;

use Illuminate\Support\Facades\Log;

trait HasPerPageSession
{
    public function perPageSessionKey(): string
    {
        return sprintf('perPage:%s', $this->getUriKey());
    }

    public function getItemsPerPage(): int
    {
        $itemsPerPage = 10;

        return (int) session()->get(
            $this->perPageSessionKey(),
            $itemsPerPage,
        );
    }

    public function perPageValues(): array
    {
        return [
            5 => 5,
            10 => 10,
            25 => 25,
            50 => 50,
            100 => 100,
            250 => 250,
            500 => 500,
            1000 => 1000,
        ];
    }
}
