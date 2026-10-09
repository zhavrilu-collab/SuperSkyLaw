<?php

namespace App\Enums;

enum MatterOutcome: string
{
    case Granted = 'granted';
    case Denied = 'denied';
    case Partial = 'partial';
    case Settlement = 'settlement';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Granted => 'Usvojen zahtjev (dobiven spor)',
            self::Denied => 'Odbijen zahtjev (izgubljen spor)',
            self::Partial => 'Djelomično usvojen zahtjev',
            self::Settlement => 'Nagodba',
            self::Withdrawn => 'Povučena tužba',
        };
    }
}
