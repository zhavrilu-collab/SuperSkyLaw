<?php

namespace App\Enums;

enum ConflictResult: string
{
    case Clear = 'clear';
    case Potential = 'potential';
    case Hard = 'hard';

    public function label(): string
    {
        return match ($this) {
            self::Clear => 'Čisto',
            self::Potential => 'Mogući sukob',
            self::Hard => 'Tvrdi sukob',
        };
    }
}
