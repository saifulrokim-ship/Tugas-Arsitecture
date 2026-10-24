<?php

namespace App\Services\Schedule;

use Carbon\CarbonInterface;

/**
 * Hasil analisis jalur kritis seluruh proyek (FR-32 s/d FR-36).
 */
class ScheduleResult
{
    /**
     * @param  array<int, TaskSchedule>  $tasks  jadwal per tugas, dikey id tugas
     * @param  list<int>  $order  urutan tugas (DFS induk lalu sub-kegiatan)
     * @param  list<array{predecessor_id: int, successor_id: int, type: string, lag: int, critical: bool}>  $edges
     */
    public function __construct(
        public readonly array $tasks,
        public readonly array $order,
        public readonly CarbonInterface $projectStart,
        public readonly CarbonInterface $projectFinish,
        public readonly int $projectDuration,
        public readonly array $edges,
    ) {}

    public function task(int $taskId): ?TaskSchedule
    {
        return $this->tasks[$taskId] ?? null;
    }

    /** @return list<int> */
    public function criticalTaskIds(): array
    {
        $ids = [];

        foreach ($this->tasks as $task) {
            if ($task->critical) {
                $ids[] = $task->taskId;
            }
        }

        return $ids;
    }

    /** @return list<int> */
    public function orderedTaskIds(): array
    {
        return $this->order;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $tasks = [];

        foreach ($this->order as $taskId) {
            $tasks[] = $this->tasks[$taskId]->toArray();
        }

        return [
            'project_start' => $this->projectStart->format('Y-m-d'),
            'project_finish' => $this->projectFinish->format('Y-m-d'),
            'project_duration' => $this->projectDuration,
            'tasks' => $tasks,
            'edges' => $this->edges,
        ];
    }
}
