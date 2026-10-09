<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CroatianCalendar
{
    public const TIMEZONE = 'Europe/Zagreb';

    public function isNonWorking(CarbonInterface $date): bool
    {
        $day = $this->local($date);

        return $day->isWeekend() || $this->isHoliday($day);
    }

    public function rollForward(CarbonInterface $date): Carbon
    {
        $day = $this->local($date)->startOfDay();

        while ($this->isNonWorking($day)) {
            $day->addDay();
        }

        return $day;
    }

    public function isHoliday(CarbonInterface $date): bool
    {
        $day = $this->local($date);
        $monthDay = $day->format('m-d');
        $fixed = [
            '01-01',
            '01-06',
            '05-01',
            '05-30',
            '06-22',
            '08-05',
            '08-15',
            '11-01',
            '11-18',
            '12-25',
            '12-26',
        ];

        if (in_array($monthDay, $fixed, true)) {
            return true;
        }

        $easter = $this->easterSunday($day->year);

        return $day->isSameDay($easter)
            || $day->isSameDay($easter->copy()->addDay())
            || $day->isSameDay($easter->copy()->addDays(60));
    }

    public function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day, 0, 0, 0, self::TIMEZONE);
    }

    public function local(CarbonInterface $date): Carbon
    {
        return Carbon::parse($date)->timezone(self::TIMEZONE);
    }
}
