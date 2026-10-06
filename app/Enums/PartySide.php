<?php

namespace App\Enums;

enum PartySide: string
{
    case Client = 'client';
    case Opposing = 'opposing';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Naš klijent',
            self::Opposing => 'Protivna strana',
            self::Other => 'Ostalo',
        };
    }

    public function conflictsWith(self $other): bool
    {
        return ($this === self::Client && $other === self::Opposing)
            || ($this === self::Opposing && $other === self::Client);
    }
}
