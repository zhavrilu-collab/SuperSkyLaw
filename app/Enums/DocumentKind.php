<?php

namespace App\Enums;

enum DocumentKind: string
{
    case Brief = 'brief';
    case Power = 'power';
    case Evidence = 'evidence';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Brief => 'Podnesak',
            self::Power => 'Punomoć',
            self::Evidence => 'Dokaz',
            self::Other => 'Ostalo',
        };
    }

    public function folder(): string
    {
        return match ($this) {
            self::Brief => 'Podnesci',
            self::Power => 'Punomoci',
            self::Evidence => 'Dokazi',
            self::Other => 'Ostalo',
        };
    }

    public static function fromFolder(?string $folder): self
    {
        return match ($folder) {
            'Podnesci' => self::Brief,
            'Punomoci' => self::Power,
            'Dokazi' => self::Evidence,
            default => self::Other,
        };
    }
}
