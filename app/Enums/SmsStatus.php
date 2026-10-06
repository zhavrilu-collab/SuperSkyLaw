<?php

namespace App\Enums;

enum SmsStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
    case SkippedNoConsent = 'skipped_no_consent';
    case SkippedNoProvider = 'skipped_no_provider';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'SMS je poslan',
            self::Failed => 'SMS nije isporučen',
            self::SkippedNoConsent => 'SMS nije poslan jer nema privole',
            self::SkippedNoProvider => 'SMS nije spojen. Poruka nije poslana i nije naplaćena',
        };
    }
}
