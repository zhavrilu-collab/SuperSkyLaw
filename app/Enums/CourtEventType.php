<?php

namespace App\Enums;

enum CourtEventType: string
{
    case Hearing = 'hearing';
    case Meeting = 'meeting';
    case Inspection = 'inspection';
    case AppealDeadline = 'appeal_deadline';
    case ObjectionDeadline = 'objection_deadline';
    case Limitation = 'limitation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hearing => 'Ročište',
            self::Meeting => 'Sastanak',
            self::Inspection => 'Očevid',
            self::AppealDeadline => 'Rok za žalbu',
            self::ObjectionDeadline => 'Rok za prigovor',
            self::Limitation => 'Zastara',
            self::Other => 'Ostalo',
        };
    }

    public function isDeadline(): bool
    {
        return in_array($this, [self::AppealDeadline, self::ObjectionDeadline, self::Limitation], true);
    }
}
