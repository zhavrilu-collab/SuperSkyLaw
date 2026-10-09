<?php

namespace App\Enums;

enum CourtType: string
{
    case Supreme = 'supreme';
    case HighCriminal = 'high_criminal';
    case HighMisdemeanor = 'high_misdemeanor';
    case HighCommercial = 'high_commercial';
    case HighAdministrative = 'high_administrative';
    case County = 'county';
    case Municipal = 'municipal';
    case MunicipalCivil = 'municipal_civil';
    case MunicipalCriminal = 'municipal_criminal';
    case MunicipalMisdemeanor = 'municipal_misdemeanor';
    case MunicipalLabor = 'municipal_labor';
    case Commercial = 'commercial';
    case Administrative = 'administrative';

    public function label(): string
    {
        return match ($this) {
            self::Supreme => 'Vrhovni sud',
            self::HighCriminal => 'Visoki kazneni sud',
            self::HighMisdemeanor => 'Visoki prekršajni sud',
            self::HighCommercial => 'Visoki trgovački sud',
            self::HighAdministrative => 'Visoki upravni sud',
            self::County => 'Županijski sudovi',
            self::Municipal, self::MunicipalCivil, self::MunicipalCriminal, self::MunicipalMisdemeanor, self::MunicipalLabor => 'Općinski sudovi',
            self::Commercial => 'Trgovački sudovi',
            self::Administrative => 'Upravni sudovi',
        };
    }
}
