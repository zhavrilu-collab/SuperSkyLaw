<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Neplaćeno',
            self::Partial => 'Djelomično plaćeno',
            self::Paid => 'Plaćeno',
            self::Overdue => 'Dospjelo',
        };
    }
}
