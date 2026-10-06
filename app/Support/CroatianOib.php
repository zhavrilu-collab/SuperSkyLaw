<?php

namespace App\Support;

use Illuminate\Support\Str;

class CroatianOib
{
    public static function isValid(string $oib): bool
    {
        $oib = preg_replace('/\s+/', '', $oib);

        if (! preg_match('/^\d{11}$/', $oib)) {
            return false;
        }

        $a = 10;
        for ($i = 0; $i < 10; $i++) {
            $a = ($a + (int) $oib[$i]) % 10;
            if ($a === 0) {
                $a = 10;
            }
            $a = ($a * 2) % 11;
        }

        $kontrolni = 11 - $a;
        if ($kontrolni === 10) {
            $kontrolni = 0;
        }

        return $kontrolni === (int) $oib[10];
    }

    public static function slugFromName(string $name): string
    {
        $slug = Str::slug($name, '-', 'hr');

        return $slug !== '' ? $slug : 'tvrtka';
    }
}
