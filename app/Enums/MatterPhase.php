<?php

namespace App\Enums;

enum MatterPhase: string
{
    case NewMatter = 'new';
    case InTime = 'in_time';
    case Urgent = 'urgent';
    case DueToday = 'due_today';
    case Waiting = 'waiting';
    case HearingSet = 'hearing';
    case Paused = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::NewMatter => 'Novi predmet',
            self::InTime => 'U roku',
            self::Urgent => 'Hitno',
            self::DueToday => 'Danas istječe',
            self::Waiting => 'Čeka se sud',
            self::HearingSet => 'Ročište zakazano',
            self::Paused => 'Pauziran',
            self::Archived => 'Arhiviran',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::NewMatter => 'faza-nova',
            self::InTime => 'faza-roku',
            self::Urgent => 'faza-hitno',
            self::DueToday => 'faza-danas',
            self::Waiting => 'faza-ceka',
            self::HearingSet => 'faza-rociste',
            self::Paused => 'faza-pauza',
            self::Archived => 'faza-arhiv',
        };
    }
}
