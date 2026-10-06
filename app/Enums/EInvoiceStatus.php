<?php

namespace App\Enums;

enum EInvoiceStatus: string
{
    case None = 'none';
    case Prepared = 'prepared';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Nije pripremljen',
            self::Prepared => 'Pripremljen, čeka slanje',
            self::Sent => 'Poslan Moj-eRačunu',
            self::Failed => 'Slanje nije uspjelo',
        };
    }
}
