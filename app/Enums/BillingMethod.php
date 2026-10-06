<?php

namespace App\Enums;

enum BillingMethod: string
{
    case Tariff = 'tariff';
    case Hourly = 'hourly';
    case Flat = 'flat';
    case SuccessFee = 'success_fee';

    public function label(): string
    {
        return match ($this) {
            self::Tariff => 'Tarifa HOK-a',
            self::Hourly => 'Ugovorena satnica',
            self::Flat => 'Paušal',
            self::SuccessFee => 'Uspjeh u sporu',
        };
    }
}
