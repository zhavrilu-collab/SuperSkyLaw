<?php

namespace App\Enums;

enum TrustDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Uplata',
            self::Out => 'Isplata',
        };
    }
}
