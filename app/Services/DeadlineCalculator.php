<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class DeadlineCalculator
{
    public function __construct(private readonly CroatianCalendar $calendar) {}

    public function due(CarbonInterface $trigger, int $amount, string $unit): Carbon
    {
        $start = $this->calendar->local($trigger)->startOfDay();
        $raw = match ($unit) {
            'days' => $start->copy()->addDays($amount),
            'months' => $start->copy()->addMonthsNoOverflow($amount),
            'years' => $start->copy()->addYearsNoOverflow($amount),
            default => throw new InvalidArgumentException('Nepoznata jedinica roka.'),
        };

        return $this->calendar->rollForward($raw);
    }

    public function expiresAt(CarbonInterface $dueDate): Carbon
    {
        return $this->calendar->local($dueDate)->setTime(23, 59)->utc();
    }
}
