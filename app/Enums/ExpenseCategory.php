<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case CourtFee = 'court_fee';
    case Expert = 'expert';
    case Travel = 'travel';
    case Postage = 'postage';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CourtFee => 'Sudska pristojba',
            self::Expert => 'Trošak vještačenja',
            self::Travel => 'Putni trošak',
            self::Postage => 'Poštarina',
            self::Other => 'Ostalo',
        };
    }
}
