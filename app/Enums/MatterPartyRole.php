<?php

namespace App\Enums;

enum MatterPartyRole: string
{
    case Client = 'client';
    case Plaintiff = 'plaintiff';
    case Defendant = 'defendant';
    case Creditor = 'creditor';
    case Debtor = 'debtor';
    case ThirdParty = 'third_party';
    case Court = 'court';
    case Judge = 'judge';
    case Expert = 'expert';
    case OpposingCounsel = 'opposing_counsel';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Klijent',
            self::Plaintiff => 'Tužitelj',
            self::Defendant => 'Tuženik',
            self::Creditor => 'Ovrhovoditelj',
            self::Debtor => 'Ovršenik',
            self::ThirdParty => 'Treća strana',
            self::Court => 'Sud',
            self::Judge => 'Sudac',
            self::Expert => 'Vještak',
            self::OpposingCounsel => 'Odvjetnik protivne strane',
        };
    }

    public function defaultSide(): PartySide
    {
        return match ($this) {
            self::Client => PartySide::Client,
            self::Defendant, self::Debtor, self::OpposingCounsel => PartySide::Opposing,
            default => PartySide::Other,
        };
    }
}
