<?php

namespace App\Enums;

enum OrganizationStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Na čekanju',
            self::Active => 'Aktivan',
            self::Suspended => 'Suspendiran',
        };
    }
}
