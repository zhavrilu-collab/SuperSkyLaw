<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PerPage
{
    /** @var list<int> */
    public const OPTIONS = [10, 25, 50, 100];

    public static function resolve(Request $request, int $default = 25): int
    {
        $value = (int) $request->integer('per_page');

        if (! in_array($value, self::OPTIONS, true)) {
            return $default;
        }

        return $value;
    }
}
