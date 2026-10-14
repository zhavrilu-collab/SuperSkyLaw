<?php

namespace App\Enums;

use App\Support\DirectoryText;

enum OfficeKind: string
{
    case Sole = 'sole';
    case Joint = 'joint';
    case Firm = 'firm';
    case ForeignBranch = 'foreign_branch';

    public function label(): string
    {
        return match ($this) {
            self::Sole => 'Samostalni odvjetnički ured',
            self::Joint => 'Zajednički odvjetnički ured',
            self::Firm => 'Odvjetničko društvo',
            self::ForeignBranch => 'Podružnica stranog odvjetničkog društva',
        };
    }

    public function usesCourtRegister(): bool
    {
        return $this === self::Firm || $this === self::ForeignBranch;
    }

    public static function fromDirectoryStatus(string $status): ?self
    {
        $folded = DirectoryText::fold($status);

        if ($folded === '' || str_contains($folded, 'vjezben') || str_contains($folded, 'ne obavlja')) {
            return null;
        }

        if (str_contains($folded, 'zajednick')) {
            return self::Joint;
        }

        if (str_contains($folded, 'drustv')) {
            return self::Firm;
        }

        if (str_contains($folded, 'odvjetni')) {
            return self::Sole;
        }

        return null;
    }
}
