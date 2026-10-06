<?php

namespace App\Enums;

enum TimeEntryStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case WrittenOff = 'written_off';
    case NonBillable = 'non_billable';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Čeka odobrenje',
            self::Approved => 'Odobreno',
            self::WrittenOff => 'Otpis',
            self::NonBillable => 'Nenaplativo',
        };
    }
}
