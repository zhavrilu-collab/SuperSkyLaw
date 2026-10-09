<?php

namespace App\Support;

class CourtCaseNumber
{
    /**
     * @return array{court_case_number: ?string, case_mark: ?string, case_number: ?string, case_year: ?int}|null
     */
    public static function parts(?string $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [
                'court_case_number' => null,
                'case_mark' => null,
                'case_number' => null,
                'case_year' => null,
            ];
        }

        if (! preg_match('/^([A-Za-zČĆŽŠĐčćžšđ0-9]+)-(\d+)\/(\d{4})(?:-[A-Za-z0-9]+)?$/u', $value, $match)) {
            return null;
        }

        return [
            'court_case_number' => $value,
            'case_mark' => $match[1],
            'case_number' => $match[2],
            'case_year' => (int) $match[3],
        ];
    }
}
