<?php

namespace App\Enums;

enum OfficePosition: string
{
    case Plaintiff = 'plaintiff';
    case Accused = 'accused';
    case Defendant = 'defendant';
    case Petitioner = 'petitioner';
    case Opposing = 'opposing';
    case Injured = 'injured';

    public function label(): string
    {
        return match ($this) {
            self::Plaintiff => 'Tužitelj',
            self::Accused => 'Okrivljenik',
            self::Defendant => 'Tuženik',
            self::Petitioner => 'Predlagatelj',
            self::Opposing => 'Protustranka',
            self::Injured => 'Oštećenik',
        };
    }
}
