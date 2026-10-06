<?php

namespace App\Enums;

enum TimelineEntryType: string
{
    case HearingNote = 'hearing_note';
    case Letter = 'letter';
    case Email = 'email';
    case Action = 'action';

    public function label(): string
    {
        return match ($this) {
            self::HearingNote => 'Zabilješka s ročišta',
            self::Letter => 'Dopis',
            self::Email => 'E-mail',
            self::Action => 'Pravna radnja',
        };
    }
}
