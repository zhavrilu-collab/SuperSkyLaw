<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class LimitationAssistant
{
    public function suggest(string $basis, Carbon $startsOn): Carbon
    {
        $years = config('limitation.bases.'.$basis.'.years');
        if (! is_int($years)) {
            throw new InvalidArgumentException('Nepoznata osnova zastare.');
        }

        return $startsOn->copy()->startOfDay()->addYears($years);
    }
}
