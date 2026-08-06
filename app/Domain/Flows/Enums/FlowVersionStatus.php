<?php

namespace App\Domain\Flows\Enums;

enum FlowVersionStatus: string
{
    case PUBLISHED = 'published';
    case DRAFT = 'draft';
    case ARCHIVED = 'archived';

    public function toString(): ?string
    {
        return match ($this) {
            self::PUBLISHED => 'Опубликована',
            self::DRAFT => 'Черновик',
            self::ARCHIVED => 'Архивирована',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PUBLISHED => 'green',
            self::DRAFT => 'yellow',
            self::ARCHIVED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PUBLISHED => 'play-circle',
            self::DRAFT => 'pencil-square',
            self::ARCHIVED => 'archive-box-arrow-down',
        };
    }
}
