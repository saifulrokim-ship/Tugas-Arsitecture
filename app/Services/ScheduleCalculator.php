<?php

namespace App\Services;

use App\Models\Dependency;
use App\Models\Project;
use App\Models\Task;
use App\Services\Schedule\CircularDependencyException;
use App\Services\Schedule\ScheduleResult;
use App\Services\Schedule\TaskSchedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Mesin penjadwalan proyek: metode Critical Path Method (CPM) dengan
 * forward pass (ES/EF), backward pass (LS/LF), total slack, dan deteksi
 * jalur kritis.
 *
 * Aturan bisnis (PRD bagian 7):
 *  1. Durasi dihitung dalam hari kerja.
 *  2. FS: mulai = selesai(predecessor) + 1 hari kerja + lag. SS: mulai = mulai(predecessor) + lag.
 *  3. Beberapa predecessor → diambil tanggal mulai yang paling akhir.
 *  4. Tanggal mulai manual tidak boleh lebih awal dari batas dependensi.
 *  6. Dependensi melingkar tidak dapat dihitung.
 */
class ScheduleCalculator
{
    /** @var Collection<int, Task> */
    private Collection $tasks;

    /** @var array<int, list<int>> */
    private array $children = [];

    /** @var array<int, list<Dependency>> */
    private array $predecessors = [];

    /** @var array<int, list<Dependency>> */
    private array $successors = [];

    /** @var array<int, TaskSchedule> */
    private array $schedule = [];

    /** @var array<int, 'visiting'|'done'> */
    private array $forwardState = [];

    /** @var array<int, 'visiting'|'done'> */
    private array $backwardState = [];

    /** @var list<int> */
    private array $forwardStack = [];

    private CarbonInterface $baseStart;

    private CarbonInterface $projectFinish;

    public function __construct(private readonly WorkCalendar $calendar) {}

    public static function forProject(Project $project): self
    {
        return new self(WorkCalendar::forProject($project));
    }

    public function calculate(Project $project): ScheduleResult
    {
        $this->reset($project);

        foreach ($this->tasks as $task) {
            $this->forwardPass((int) $task->id);
        }

        $projectStart = $this->projectStart();
        $this->projectFinish = $this->projectFinish();

        foreach ($this->tasks as $task) {
            $this->backwardPass((int) $task->id);
        }

        $this->resolveSummarySlack();

        return new ScheduleResult(
            tasks: $this->schedule,
            order: $this->buildOrder(),
            projectStart: $projectStart,
            projectFinish: $this->projectFinish,
            projectDuration: $this->calendar->workingDaysBetween($projectStart, $this->projectFinish),
            edges: $this->buildEdges(),
        );
    }

    private function reset(Project $project): void
    {
        $project->loadMissing(['calendar', 'holidays']);

        $this->tasks = $project->tasks()->with('resources:id,name,contact')->get()->keyBy('id');
        $this->children = [];
        $this->predecessors = [];
        $this->successors = [];
        $this->schedule = [];
        $this->forwardState = [];
        $this->backwardState = [];
        $this->forwardStack = [];
        $this->baseStart = $this->calendar->adjustToWorkingDay($project->start_date);

        foreach ($this->tasks as $task) {
            if ($task->parent_id !== null && $this->tasks->has($task->parent_id)) {
                $this->children[$task->parent_id][] = (int) $task->id;
            }
        }

        foreach ($this->children as $parentId => $childIds) {
            $this->children[$parentId] = $this->sortTaskIds($childIds);
        }

        foreach ($project->dependencies as $dependency) {
            $this->addDependency($dependency);
        }
    }

    private function addDependency(Dependency $dependency): void
    {
        $predecessorId = (int) $dependency->predecessor_id;
        $successorId = (int) $dependency->successor_id;

        if ($predecessorId === $successorId) {
            throw new CircularDependencyException([$this->taskName($predecessorId)]);
        }

        if (! $this->tasks->has($predecessorId) || ! $this->tasks->has($successorId)) {
            return;
        }

        $this->predecessors[$successorId][] = $dependency;
        $this->successors[$predecessorId][] = $dependency;
    }

    /**
     * Forward pass: menentukan Early Start (ES) dan Early Finish (EF).
     */
    private function forwardPass(int $taskId): TaskSchedule
    {
        $state = $this->forwardState[$taskId] ?? null;

        if ($state === 'done') {
            return $this->schedule[$taskId];
        }

        if ($state === 'visiting') {
            throw $this->circularDependency($taskId);
        }

        $this->forwardState[$taskId] = 'visiting';
        $this->forwardStack[] = $taskId;

        $task = $this->tasks->get($taskId);
        $childIds = $this->children[$taskId] ?? [];

        if ($task === null) {
            array_pop($this->forwardStack);

            throw new RuntimeException("Tugas dengan id {$taskId} tidak ditemukan.");
        }

        if ($childIds !== []) {
            [$start, $finish] = $this->aggregateChildren($childIds);
            $duration = max(1, $this->calendar->workingDaysBetween($start, $finish));
        } else {
            $start = $this->resolveStart($taskId, $task);
            $duration = max(0, (int) $task->duration);
            $finish = $duration === 0
                ? $start->copy()
                : $this->calendar->addWorkingDays($start, $duration - 1);
        }

        $this->schedule[$taskId] = $this->makeSchedule(
            task: $task,
            duration: $duration,
            start: $start,
            finish: $finish,
            latestStart: $finish,
            latestFinish: $finish,
            slack: 0,
        );

        $this->forwardState[$taskId] = 'done';
        array_pop($this->forwardStack);

        return $this->schedule[$taskId];
    }

    /**
     * Tugas induk otomatis mengambil tanggal mulai terawal dan selesai
     * terakhir dari sub-kegiatannya (FR-10).
     *
     * @param  list<int>  $childIds
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function aggregateChildren(array $childIds): array
    {
        $start = null;
        $finish = null;

        foreach ($childIds as $childId) {
            $child = $this->forwardPass($childId);

            if ($start === null || $child->start->lt($start)) {
                $start = $child->start;
            }

            if ($finish === null || $child->finish->gt($finish)) {
                $finish = $child->finish;
            }
        }

        return [$start, $finish];
    }

    private function resolveStart(int $taskId, Task $task): CarbonInterface
    {
        $constraint = null;

        foreach ($this->predecessors[$taskId] ?? [] as $dependency) {
            $predecessor = $this->forwardPass((int) $dependency->predecessor_id);
            $candidate = $this->constraintStart($dependency, $predecessor);

            if ($constraint === null || $candidate->gt($constraint)) {
                $constraint = $candidate;
            }
        }

        if ($task->start_date === null) {
            return $constraint ?? $this->baseStart;
        }

        $manualStart = $this->calendar->adjustToWorkingDay($task->start_date);

        if ($constraint !== null && $manualStart->lt($constraint)) {
            return $constraint;
        }

        return $manualStart;
    }

    private function constraintStart(Dependency $dependency, TaskSchedule $predecessor): CarbonInterface
    {
        if ($dependency->type === Dependency::TYPE_START_TO_START) {
            return $this->calendar->addWorkingDays($predecessor->start, (int) $dependency->lag);
        }

        return $this->calendar->addWorkingDays($predecessor->finish, 1 + (int) $dependency->lag);
    }

    /**
     * Backward pass: menentukan Late Start (LS) dan Late Finish (LF).
     */
    private function backwardPass(int $taskId): TaskSchedule
    {
        $state = $this->backwardState[$taskId] ?? null;

        if ($state === 'done') {
            return $this->schedule[$taskId];
        }

        if ($state === 'visiting') {
            return $this->schedule[$taskId];
        }

        $this->backwardState[$taskId] = 'visiting';

        $schedule = $this->schedule[$taskId];
        $latestFinish = null;

        foreach ($this->successors[$taskId] ?? [] as $dependency) {
            $successor = $this->backwardPass((int) $dependency->successor_id);
            $candidate = $this->latestFinishFor($dependency, $schedule->duration, $successor);

            if ($latestFinish === null || $candidate->lt($latestFinish)) {
                $latestFinish = $candidate;
            }
        }

        if ($latestFinish === null) {
            $latestFinish = $this->projectFinish;
        } elseif ($latestFinish->gt($this->projectFinish)) {
            $latestFinish = $this->projectFinish;
        }

        $latestStart = $schedule->duration === 0
            ? $latestFinish
            : $this->calendar->subtractWorkingDays($latestFinish, $schedule->duration - 1);
        $slack = $this->slackBetween($schedule->start, $latestStart);

        $this->schedule[$taskId] = $this->makeSchedule(
            task: $this->tasks->get($taskId),
            duration: $schedule->duration,
            start: $schedule->start,
            finish: $schedule->finish,
            latestStart: $latestStart,
            latestFinish: $latestFinish,
            slack: $slack,
        );

        $this->backwardState[$taskId] = 'done';

        return $this->schedule[$taskId];
    }

    private function latestFinishFor(Dependency $dependency, int $duration, TaskSchedule $successor): CarbonInterface
    {
        if ($dependency->type === Dependency::TYPE_START_TO_START) {
            $latestStart = $this->calendar->subtractWorkingDays($successor->latestStart, (int) $dependency->lag);

            // Milestone (durasi 0) berakhir tepat pada tanggal mulainya.
            return $duration === 0
                ? $latestStart
                : $this->calendar->addWorkingDays($latestStart, $duration - 1);
        }

        return $this->calendar->subtractWorkingDays($successor->latestStart, 1 + (int) $dependency->lag);
    }

    private function slackBetween(CarbonInterface $start, CarbonInterface $latestStart): int
    {
        if ($latestStart->gte($start)) {
            return max(0, $this->calendar->workingDaysBetween($start, $latestStart) - 1);
        }

        return -(max(0, $this->calendar->workingDaysBetween($latestStart, $start) - 1));
    }

    /**
     * Slack tugas induk = slack terkecil di antara sub-kegiatannya.
     */
    private function resolveSummarySlack(): void
    {
        foreach (array_keys($this->children) as $parentId) {
            $this->summarySlack($parentId);
        }
    }

    private function summarySlack(int $taskId): int
    {
        $schedule = $this->schedule[$taskId] ?? null;

        if ($schedule === null) {
            return PHP_INT_MAX;
        }

        $slack = $schedule->slack;

        foreach ($this->children[$taskId] ?? [] as $childId) {
            $slack = min($slack, $this->summarySlack($childId));
        }

        if ($slack !== $schedule->slack) {
            $this->schedule[$taskId] = $this->makeSchedule(
                task: $this->tasks->get($taskId),
                duration: $schedule->duration,
                start: $schedule->start,
                finish: $schedule->finish,
                latestStart: $schedule->latestStart,
                latestFinish: $schedule->latestFinish,
                slack: $slack,
            );
        }

        return $slack;
    }

    /**
     * @return list<int>
     */
    private function buildOrder(): array
    {
        $rootIds = [];

        foreach ($this->tasks as $task) {
            $isRoot = $task->parent_id === null || ! $this->tasks->has($task->parent_id);

            if ($isRoot) {
                $rootIds[] = (int) $task->id;
            }
        }

        $order = [];

        foreach ($this->sortTaskIds($rootIds) as $rootId) {
            $this->appendOrder($rootId, $order);
        }

        return $order;
    }

    /**
     * @param  list<int>  $order
     */
    private function appendOrder(int $taskId, array &$order): void
    {
        $order[] = $taskId;

        foreach ($this->children[$taskId] ?? [] as $childId) {
            $this->appendOrder($childId, $order);
        }
    }

    /**
     * @return list<array{predecessor_id: int, successor_id: int, type: string, lag: int, critical: bool}>
     */
    private function buildEdges(): array
    {
        $edges = [];

        foreach ($this->predecessors as $successorId => $dependencies) {
            foreach ($dependencies as $dependency) {
                $predecessorId = (int) $dependency->predecessor_id;
                $predecessor = $this->schedule[$predecessorId] ?? null;
                $successor = $this->schedule[$successorId] ?? null;

                $edges[] = [
                    'predecessor_id' => $predecessorId,
                    'successor_id' => (int) $successorId,
                    'type' => $dependency->type,
                    'lag' => (int) $dependency->lag,
                    'critical' => (bool) ($predecessor?->critical && $successor?->critical),
                ];
            }
        }

        return $edges;
    }

    /**
     * @param  list<int>  $taskIds
     * @return list<int>
     */
    private function sortTaskIds(array $taskIds): array
    {
        usort($taskIds, function (int $first, int $second): int {
            $firstTask = $this->tasks->get($first);
            $secondTask = $this->tasks->get($second);

            if ($firstTask === null || $secondTask === null) {
                return $first <=> $second;
            }

            return [$firstTask->sort_order, $firstTask->id] <=> [$secondTask->sort_order, $secondTask->id];
        });

        return $taskIds;
    }

    private function makeSchedule(
        Task $task,
        int $duration,
        CarbonInterface $start,
        CarbonInterface $finish,
        CarbonInterface $latestStart,
        CarbonInterface $latestFinish,
        int $slack,
    ): TaskSchedule {
        $assignees = $task->resources
            ->map(fn ($resource): array => [
                'id' => (int) $resource->id,
                'name' => $resource->name,
                'contact' => $resource->contact,
            ])
            ->all();

        return new TaskSchedule(
            taskId: (int) $task->id,
            name: $task->name,
            parentId: $task->parent_id !== null ? (int) $task->parent_id : null,
            priority: $task->priority,
            isSummary: isset($this->children[(int) $task->id]),
            isMilestone: (int) $task->duration === 0 && ! isset($this->children[(int) $task->id]),
            duration: $duration,
            percentComplete: max(0, min(100, (int) $task->percent_complete)),
            start: $start,
            finish: $finish,
            latestStart: $latestStart,
            latestFinish: $latestFinish,
            slack: $slack,
            critical: $slack <= 0,
            assignees: $assignees,
        );
    }

    private function projectStart(): CarbonInterface
    {
        $start = null;

        foreach ($this->schedule as $taskSchedule) {
            if ($start === null || $taskSchedule->start->lt($start)) {
                $start = $taskSchedule->start;
            }
        }

        return $start ?? $this->baseStart;
    }

    private function projectFinish(): CarbonInterface
    {
        $finish = null;

        foreach ($this->schedule as $taskSchedule) {
            if ($finish === null || $taskSchedule->finish->gt($finish)) {
                $finish = $taskSchedule->finish;
            }
        }

        return $finish ?? $this->baseStart;
    }

    private function taskName(int $taskId): string
    {
        return $this->tasks->get($taskId)?->name ?? "#{$taskId}";
    }

    private function circularDependency(int $taskId): CircularDependencyException
    {
        $position = array_search($taskId, $this->forwardStack, true);

        $cycle = $position === false
            ? [$taskId]
            : array_slice($this->forwardStack, $position);

        $cycle[] = $taskId;

        return new CircularDependencyException(array_map(fn (int $id): string => $this->taskName($id), $cycle));
    }
}
