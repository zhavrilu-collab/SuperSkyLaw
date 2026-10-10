<?php

namespace App\Enums;

enum StatuteArea: string
{
    case Civil = 'civil';
    case Criminal = 'criminal';
    case Commercial = 'commercial';
    case Labor = 'labor';
    case Enforcement = 'enforcement';
    case Administrative = 'administrative';
    case Tax = 'tax';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Civil => 'Građansko',
            self::Criminal => 'Kazneno',
            self::Commercial => 'Trgovačko',
            self::Labor => 'Radno',
            self::Enforcement => 'Ovrha i osiguranje',
            self::Administrative => 'Upravno',
            self::Tax => 'Porezno',
            self::Other => 'Ostalo',
        };
    }
}
