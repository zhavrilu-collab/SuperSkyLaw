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
            self::Enforcement => 'Ovrha i osiguranje',
            self::Administrative => 'Upravno',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Civil => 'Parnični i izvanparnični postupci',
            self::Criminal => 'Obrana i zastupanje oštećenika',
            self::Commercial => 'Gospodarski sporovi i stečajevi',
            self::Labor => 'Zastupanje radnika ili poslodavaca',
            self::Enforcement => 'Ovrha i osiguranje',
            self::Administrative => 'Postupci pred tijelima i sudovima',
        };
    }
}
