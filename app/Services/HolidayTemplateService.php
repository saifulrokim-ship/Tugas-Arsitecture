<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Project;

/**
 * Template hari libur nasional Indonesia (PRD CAL-4): satu klik, aman
 * diulang tanpa membuat duplikat.
 */
class HolidayTemplateService
{
    /**
     * Hari libur nasional yang tanggalnya sama setiap tahun.
     *
     * @var array<string, string>
     */
    private const FIXED_HOLIDAYS = [
        '01-01' => 'Tahun Baru Masehi',
        '05-01' => 'Hari Buruh Internasional',
        '06-01' => 'Hari Lahir Pancasila',
        '08-17' => 'Proklamasi Kemerdekaan RI',
        '12-25' => 'Hari Raya Natal',
    ];

    /**
     * Hari libur bergerak (tidak dapat dihitung tanpa kalender Hijriah):
     * dipetakan per tahun agar template tetap satu klik dan akurat.
     * Berupa list pasangan [tanggal 'MM-DD', keterangan] karena dua perayaan
     * dapat jatuh pada tanggal yang sama.
     *
     * @var array<int, list<array{0: string, 1: string}>>
     */
    private const MOVING_HOLIDAYS = [
        2025 => [
            ['03-29', 'Hari Raya Nyepi'],
            ['03-31', 'Idulfitri 1446 H'],
            ['04-01', 'Idulfitri 1446 H (cuti bersama)'],
            ['05-12', 'Hari Raya Waisak'],
            ['06-07', 'Iduladha 1446 H'],
            ['01-27', 'Isra Miraj Nabi Muhammad'],
            ['09-05', 'Maulid Nabi Muhammad'],
        ],
        2026 => [
            ['03-19', 'Hari Raya Nyepi'],
            ['03-20', 'Idulfitri 1447 H'],
            ['03-23', 'Idulfitri 1447 H (cuti bersama)'],
            ['05-26', 'Iduladha 1447 H'],
            ['05-27', 'Hari Raya Waisak'],
            ['05-27', 'Isra Miraj Nabi Muhammad'],
            ['09-05', 'Maulid Nabi Muhammad'],
        ],
        2027 => [
            ['03-09', 'Hari Raya Nyepi'],
            ['03-09', 'Idulfitri 1448 H'],
            ['03-10', 'Idulfitri 1448 H (cuti bersama)'],
            ['05-16', 'Iduladha 1448 H'],
            ['05-17', 'Isra Miraj Nabi Muhammad'],
            ['06-14', 'Hari Raya Waisak'],
            ['08-25', 'Maulid Nabi Muhammad'],
        ],
    ];

    /**
     * Insert seluruh hari libur template untuk tahun berjalan proyek.
     * Tanggal yang sudah terdaftar dilewati sehingga aman diulang.
     *
     * @return array{created: int, skipped: int, year: int}
     */
    public function import(Project $project): array
    {
        $year = (int) ($project->start_date->format('Y') ?: now()->format('Y'));

        $existing = $project->holidays()
            ->whereBetween('date', ["{$year}-01-01", "{$year}-12-31"])
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->all();

        $existingIndex = array_fill_keys($existing, true);

        $created = 0;
        $skipped = 0;

        foreach ($this->holidaysFor($year) as $date => $description) {
            $key = sprintf('%04d-%s', $year, $date);

            if (isset($existingIndex[$key])) {
                $skipped++;

                continue;
            }

            Holiday::create([
                'project_id' => $project->id,
                'date' => $key,
                'description' => $description,
            ]);

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped, 'year' => $year];
    }

    /**
     * @return array<string, string>
     */
    public function holidaysFor(int $year): array
    {
        $holidays = self::FIXED_HOLIDAYS;

        foreach (self::MOVING_HOLIDAYS[$year] ?? [] as [$date, $description]) {
            $holidays[$date] = isset($holidays[$date])
                ? $holidays[$date].' & '.$description
                : $description;
        }

        ksort($holidays);

        return $holidays;
    }
}
