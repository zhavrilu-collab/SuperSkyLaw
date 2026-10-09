<?php

namespace App\Rules;

use App\Support\CourtCaseNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CourtCaseNumberRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || CourtCaseNumber::parts($value) === null) {
            $fail('Broj predmeta upišite kao P-123/2026.');
        }
    }
}
