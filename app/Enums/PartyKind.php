<?php

namespace App\Enums;

enum PartyKind: string
{
    case Person = 'person';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Person => 'Fizička osoba',
            self::Company => 'Pravna osoba',
        };
    }
}
