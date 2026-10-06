<?php

namespace App\Enums;

enum MatterKind: string
{
    case Civil = 'civil';
    case Criminal = 'criminal';
    case Commercial = 'commercial';
    case Labor = 'labor';
    case Enforcement = 'enforcement';
    case Administrative = 'administrative';

    public function label(): string
    {
        return match ($this) {
            self::Civil => 'Građansko',
            self::Criminal => 'Kazneno',
            self::Commercial => 'Trgovačko',
            self::Labor => 'Radno',
            self::Enforcement => 'Ovrha',
            self::Administrative => 'Upravno',
        };
    }
}
