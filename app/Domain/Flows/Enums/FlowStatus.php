<?php

namespace App\Domain\Flows\Enums;

enum FlowStatus: string
{
    case ACTIVE = 'active';
    case DRAFT = 'draft';
    case ARCHIVED = 'archived';

    public function toString(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'Активен',
            self::DRAFT => 'Черновик',
            self::ARCHIVED => 'Архивирован',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::DRAFT => 'yellow',
            self::ARCHIVED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'play-circle',
            self::DRAFT => 'pencil-square',
            self::ARCHIVED => 'archive-box-arrow-down',
        };
    }
}
