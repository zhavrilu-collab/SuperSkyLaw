<?php

namespace Tests\Unit;

use App\Services\CroatianCalendar;
use App\Services\DeadlineCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeadlineCalculatorTest extends TestCase
{
    private DeadlineCalculator $calculator;

    private CroatianCalendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendar = new CroatianCalendar;
        $this->calculator = new DeadlineCalculator($this->calendar);
    }

    public function test_easter_and_corpus_christi_match_known_dates(): void
    {
        $this->assertSame('2024-03-31', $this->calendar->easterSunday(2024)->toDateString());
        $this->assertSame('2026-04-05', $this->calendar->easterSunday(2026)->toDateString());
        $this->assertTrue($this->calendar->isHoliday(Carbon::parse('2026-04-06', CroatianCalendar::TIMEZONE)));
        $this->assertTrue($this->calendar->isHoliday(Carbon::parse('2026-06-04', CroatianCalendar::TIMEZONE)));
        $this->assertTrue($this->calendar->isHoliday(Carbon::parse('2026-01-06', CroatianCalendar::TIMEZONE)));
    }

    public function test_day_zero_is_not_counted_and_saturday_rolls_to_monday(): void
    {
        $friday = Carbon::parse('2026-03-06', CroatianCalendar::TIMEZONE);
        $this->assertTrue($friday->isFriday());

        $due = $this->calculator->due($friday, 8, 'days');

        $this->assertSame('2026-03-16', $due->toDateString());
        $this->assertTrue($due->isMonday());
    }

    public function test_holiday_on_the_last_day_rolls_to_the_next_working_day(): void
    {
        $due = $this->calculator->due(Carbon::parse('2025-12-29', CroatianCalendar::TIMEZONE), 8, 'days');

        $this->assertSame('2026-01-07', $due->toDateString());
    }

    public function test_months_clamp_to_the_last_day_and_then_roll_weekends(): void
    {
        $rolledSaturday = $this->calculator->due(Carbon::parse('2025-01-15', CroatianCalendar::TIMEZONE), 1, 'months');
        $this->assertSame('2025-02-17', $rolledSaturday->toDateString());

        $clamped = $this->calculator->due(Carbon::parse('2024-08-31', CroatianCalendar::TIMEZONE), 6, 'months');
        $this->assertSame('2025-02-28', $clamped->toDateString());

        $endOfMonth = $this->calculator->due(Carbon::parse('2026-01-31', CroatianCalendar::TIMEZONE), 1, 'months');
        $this->assertSame('2026-03-02', $endOfMonth->toDateString());
    }
}
