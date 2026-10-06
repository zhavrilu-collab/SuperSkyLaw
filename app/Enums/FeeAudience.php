<?php

namespace App\Enums;

enum FeeAudience: string
{
    case Client = 'client';
    case Opposing = 'opposing';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Nagrada klijentu',
            self::Opposing => 'Trošak protivne strane',
        };
    }
}
