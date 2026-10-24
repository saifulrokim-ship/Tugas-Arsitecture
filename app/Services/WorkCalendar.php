<?php

namespace App\Services;

use App\Models\Project;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Kalender kerja satu proyek: hari kerja, akhir pekan, dan hari libur khusus.
 *
 * Semua perhitungan durasi hanya menghitung hari kerja efektif (FR-06).
 */
class WorkCalendar
{
    public const DEFAULT_WORKDAYS = [1, 2, 3, 4, 5];

    public const DEFAULT_WEEKEND_DAYS = [6, 7];

    /** Nama hari sesuai urutan ISO (1 = Senin .. 7 = Minggu). */
    public const DAY_NAMES = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    /** @var array<int, true> */
    private array $workdayIndex = [];

    /** @var array<int, true> */
    private array $weekendIndex = [];

    /** @var array<string, true> */
    private array $holidayIndex = [];

    /**
     * @param  list<int>  $workdays  hari kerja (ISO 1 = Senin .. 7 = Minggu)
     * @param  list<int>  $weekendDays  hari libur akhir pekan
     * @param  list<string>  $holidays  tanggal libur dalam format Y-m-d
     */
    public function __construct(array $workdays, array $weekendDays, array $holidays = [])
    {
        foreach (array_values(array_unique(array_map('intval', $workdays))) as $day) {
            $this->workdayIndex[$day] = true;
        }

        foreach (array_values(array_unique(array_map('intval', $weekendDays))) as $day) {
            $this->weekendIndex[$day] = true;
        }

        foreach ($holidays as $holiday) {
            $this->holidayIndex[$this->format($holiday)] = true;
        }
    }

    public static function forProject(Project $project): self
    {
        $calendar = $project->calendar;
        $workdays = $calendar?->workdays ?: self::DEFAULT_WORKDAYS;
        $weekendDays = $calendar?->weekend_days ?: self::DEFAULT_WEEKEND_DAYS;

        $holidays = [];

        foreach ($project->holidays as $holiday) {
            $end = $holiday->end_date ?? $holiday->date;

            if ($end->lt($holiday->date)) {
                $end = $holiday->date;
            }

            for ($day = $holiday->date->copy(); $day->lte($end); $day = $day->addDay()) {
                $holidays[] = $day->format('Y-m-d');
            }
        }

        return new self($workdays, $weekendDays, $holidays);
    }

    public function isWorkingDay(CarbonInterface|string $date): bool
    {
        $day = $this->toCarbon($date);
        $isoDay = (int) $day->format('N');

        if (isset($this->weekendIndex[$isoDay])) {
            return false;
        }

        if (! isset($this->workdayIndex[$isoDay])) {
            return false;
        }

        return ! isset($this->holidayIndex[$day->format('Y-m-d')]);
    }

    public function isHoliday(CarbonInterface|string $date): bool
    {
        return isset($this->holidayIndex[$this->toCarbon($date)->format('Y-m-d')]);
    }

    /**
     * Majukan tanggal ke hari kerja berikutnya bila tanggal awal bukan hari kerja.
     */
    public function adjustToWorkingDay(CarbonInterface|string $date): CarbonInterface
    {
        $day = $this->toCarbon($date);

        while (! $this->isWorkingDay($day)) {
            $day = $day->copy()->addDay();
        }

        return $day;
    }

    /**
     * Tambah n hari kerja (n negatif berarti mundur). Tanggal dasar disesuaikan
     * ke hari kerja terlebih dahulu.
     */
    public function addWorkingDays(CarbonInterface|string $date, int $days): CarbonInterface
    {
        $day = $this->adjustToWorkingDay($date);

        if ($days < 0) {
            return $this->subtractWorkingDays($day, abs($days));
        }

        for ($i = 0; $i < $days; $i++) {
            do {
                $day = $day->copy()->addDay();
            } while (! $this->isWorkingDay($day));
        }

        return $day;
    }

    /**
     * Mundurkan n hari kerja dari tanggal dasar.
     */
    public function subtractWorkingDays(CarbonInterface|string $date, int $days): CarbonInterface
    {
        $day = $this->toCarbon($date);

        if ($days < 0) {
            return $this->addWorkingDays($day, abs($days));
        }

        for ($i = 0; $i < $days; $i++) {
            do {
                $day = $day->copy()->subDay();
            } while (! $this->isWorkingDay($day));
        }

        return $day;
    }

    /**
     * Jumlah hari kerja efektif dalam rentang [from, to] (inklusif).
     * Mengembalikan 0 bila rentang terbalik.
     */
    public function workingDaysBetween(CarbonInterface|string $from, CarbonInterface|string $to): int
    {
        $start = $this->toCarbon($from);
        $end = $this->toCarbon($to);

        if ($end->lessThan($start)) {
            return 0;
        }

        $count = 0;

        for ($day = $start->copy(); $day->lte($end); $day = $day->addDay()) {
            if ($this->isWorkingDay($day)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Semua hari dalam rentang [from, to] untuk rendering Gantt.
     *
     * @return list<array{date: string, working: bool}>
     */
    public function daysBetween(CarbonInterface|string $from, CarbonInterface|string $to): array
    {
        $start = $this->toCarbon($from);
        $end = $this->toCarbon($to);
        $days = [];

        for ($day = $start->copy(); $day->lte($end); $day = $day->addDay()) {
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'working' => $this->isWorkingDay($day),
            ];
        }

        return $days;
    }

    private function toCarbon(CarbonInterface|string $date): CarbonInterface
    {
        if (is_string($date)) {
            return Carbon::parse($date)->startOfDay();
        }

        return $date->copy()->startOfDay();
    }

    private function format(mixed $date): string
    {
        if (is_string($date)) {
            return Carbon::parse($date)->format('Y-m-d');
        }

        if ($date instanceof CarbonInterface) {
            return $date->format('Y-m-d');
        }

        return Carbon::parse($date)->format('Y-m-d');
    }
}
