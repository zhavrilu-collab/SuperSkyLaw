<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIban implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $value) ?? '');

        if (! preg_match('/^HR\d{19}$/', $iban)) {
            $fail('IBAN mora biti hrvatski (HR i 19 znamenki).');

            return;
        }

        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }

        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) ($remainder.$chunk) % 97;
        }

        if ($remainder !== 1) {
            $fail('IBAN nije ispravan.');
        }
    }
}
