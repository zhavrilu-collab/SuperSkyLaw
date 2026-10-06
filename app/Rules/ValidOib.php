<?php

namespace App\Rules;

use App\Support\CroatianOib;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidOib implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! CroatianOib::isValid((string) $value)) {
            $fail('OIB nije ispravan.');
        }
    }
}
