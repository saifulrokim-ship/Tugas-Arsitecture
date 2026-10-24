<?php

namespace Tests\Unit;

use App\Services\WorkCalendar;
use PHPUnit\Framework\TestCase;

class WorkCalendarTest extends TestCase
{
    /**
     * @param  list<string>  $holidays
     */
    private function calendar(array $holidays = []): WorkCalendar
    {
        return new WorkCalendar([1, 2, 3, 4, 5], [6, 7], $holidays);
    }

    public function test_add_zero_working_days_keeps_the_date(): void
    {
        $this->assertSame('2026-01-05', $this->calendar()->addWorkingDays('2026-01-05', 0)->format('Y-m-d'));
    }

    public function test_add_working_days_skips_weekend(): void
    {
        $calendar = $this->calendar();

        $this->assertSame('2026-01-12', $calendar->addWorkingDays('2026-01-09', 1)->format('Y-m-d'));
        $this->assertSame('2026-01-13', $calendar->addWorkingDays('2026-01-09', 2)->format('Y-m-d'));
        $this->assertSame('2026-01-07', $calendar->addWorkingDays('2026-01-05', 2)->format('Y-m-d'));
    }

    public function test_add_working_days_skips_custom_holidays(): void
    {
        $calendar = $this->calendar(['2026-01-08']);

        $this->assertSame('2026-01-09', $calendar->addWorkingDays('2026-01-07', 1)->format('Y-m-d'));
        $this->assertTrue($calendar->isHoliday('2026-01-08'));
    }

    public function test_subtract_working_days_moves_backwards(): void
    {
        $calendar = $this->calendar();

        $this->assertSame('2026-01-07', $calendar->subtractWorkingDays('2026-01-08', 1)->format('Y-m-d'));
        $this->assertSame('2026-01-05', $calendar->subtractWorkingDays('2026-01-08', 3)->format('Y-m-d'));
        $this->assertSame('2026-01-02', $calendar->subtractWorkingDays('2026-01-05', 1)->format('Y-m-d'));
    }

    public function test_adjust_to_working_day_moves_weekend_forward(): void
    {
        $calendar = $this->calendar();

        $this->assertSame('2026-01-12', $calendar->adjustToWorkingDay('2026-01-10')->format('Y-m-d'));
        $this->assertSame('2026-01-05', $calendar->adjustToWorkingDay('2026-01-05')->format('Y-m-d'));
    }

    public function test_working_days_between_counts_both_bounds(): void
    {
        $calendar = $this->calendar();

        $this->assertSame(5, $calendar->workingDaysBetween('2026-01-05', '2026-01-09'));
        $this->assertSame(1, $calendar->workingDaysBetween('2026-01-05', '2026-01-05'));
        $this->assertSame(6, $calendar->workingDaysBetween('2026-01-05', '2026-01-12'));
        $this->assertSame(0, $calendar->workingDaysBetween('2026-01-12', '2026-01-05'));
    }

    public function test_custom_weekend_days_are_not_working_days(): void
    {
        $calendar = new WorkCalendar([3, 4, 5], [1, 2]);

        $this->assertFalse($calendar->isWorkingDay('2026-01-05'));
        $this->assertFalse($calendar->isWorkingDay('2026-01-06'));
        $this->assertTrue($calendar->isWorkingDay('2026-01-07'));
        $this->assertSame('2026-01-08', $calendar->addWorkingDays('2026-01-07', 1)->format('Y-m-d'));
    }

    public function test_weekend_takes_precedence_over_workdays(): void
    {
        $calendar = new WorkCalendar([5, 6], [6]);

        $this->assertFalse($calendar->isWorkingDay('2026-01-10'));
        $this->assertTrue($calendar->isWorkingDay('2026-01-09'));
    }
}
