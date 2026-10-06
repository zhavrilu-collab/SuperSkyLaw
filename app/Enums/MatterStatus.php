<?php

namespace App\Enums;

enum MatterStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktivan',
            self::Paused => 'Pauziran',
            self::Archived => 'Arhiviran',
        };
    }
}
