<?php

namespace App\Services\Schedule;

use Carbon\CarbonInterface;

/**
 * Hasil penjadwalan satu tugas: tanggal rencana (ES/EF), tanggal terlambat
 * (LS/LF), slack, dan status jalur kritis.
 */
class TaskSchedule
{
    /**
     * @param  list<array{id: int, name: string, contact: string|null}>  $assignees
     */
    public function __construct(
        public readonly int $taskId,
        public readonly string $name,
        public readonly ?int $parentId,
        public readonly string $priority,
        public readonly bool $isSummary,
        public readonly bool $isMilestone,
        public readonly int $duration,
        public readonly int $percentComplete,
        public readonly CarbonInterface $start,
        public readonly CarbonInterface $finish,
        public readonly CarbonInterface $latestStart,
        public readonly CarbonInterface $latestFinish,
        public readonly int $slack,
        public readonly bool $critical,
        public readonly array $assignees = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->taskId,
            'name' => $this->name,
            'parent_id' => $this->parentId,
            'priority' => $this->priority,
            'is_summary' => $this->isSummary,
            'is_milestone' => $this->isMilestone,
            'percent_complete' => $this->percentComplete,
            'duration' => $this->duration,
            'start' => $this->start->format('Y-m-d'),
            'finish' => $this->finish->format('Y-m-d'),
            'latest_start' => $this->latestStart->format('Y-m-d'),
            'latest_finish' => $this->latestFinish->format('Y-m-d'),
            'slack' => $this->slack,
            'critical' => $this->critical,
            'assignees' => $this->assignees,
        ];
    }
}
