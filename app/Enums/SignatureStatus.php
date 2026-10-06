<?php

namespace App\Enums;

enum SignatureStatus: string
{
    case Prepared = 'prepared';
    case Sent = 'sent';
    case Signed = 'signed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Prepared => 'Pripremljeno, dokument nije potpisan',
            self::Sent => 'Poslano pružatelju, čeka se kvalificirani potpis',
            self::Signed => 'Kvalificirano potpisano',
            self::Failed => 'Slanje potpisa nije uspjelo',
        };
    }
}
