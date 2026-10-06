<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Lawyer = 'lawyer';
    case Trainee = 'trainee';
    case Secretary = 'secretary';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Partner',
            self::Lawyer => 'Odvjetnik',
            self::Trainee => 'Vježbenik',
            self::Secretary => 'Tajništvo',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromMixed(string $role): ?self
    {
        return self::tryFrom($role);
    }
}
