<?php

namespace App\Http\Controllers;

use App\Models\Dependency;
use App\Models\Project;
use App\Models\Task;
use App\Services\Schedule\CircularDependencyException;
use App\Services\Schedule\ScheduleResult;
use App\Services\ScheduleCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        [$tasks, $childrenMap] = $this->taskTree($project);

        $schedule = $this->schedule($project);
        $warnings = $this->resourceWarnings($project, $schedule);
        $predecessors = Dependency::where('project_id', $project->id)
            ->with(['predecessor:id,name', 'successor:id,name'])
            ->get()
            ->groupBy('successor_id');

        $resources = $project->resources()->orderBy('name')->get();

        // Filter per anggota (ASN-5).
        $memberId = $request->query('member') !== null ? (int) $request->query('member') : null;

        if ($memberId !== null && ! $resources->contains('id', $memberId)) {
            $memberId = null;
        }

        if ($memberId !== null) {
            [$tasks, $childrenMap] = $this->filterTreeByMember($tasks, $childrenMap, $memberId);
        }

        return view('tasks.index', compact(
            'project',
            'tasks',
            'childrenMap',
            'schedule',
            'warnings',
            'predecessors',
            'resources',
            'memberId',
        ));
    }

    public function create(Project $project, Request $request): View
    {
        [$tasks] = $this->taskTree($project);

        $parentId = $request->query('parent') !== null ? (int) $request->query('parent') : null;

        if ($parentId !== null && ! $tasks->contains('id', $parentId)) {
            abort(404);
        }

        $resources = $project->resources()->orderBy('name')->get();

        return view('tasks.create', compact('project', 'tasks', 'resources', 'parentId'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validateTask($request, $project);

        $task = Task::create([
            'project_id' => $project->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'duration' => $validated['duration'],
            'percent_complete' => $validated['percent_complete'] ?? 0,
            'priority' => $validated['priority'],
            'sort_order' => $this->nextSortOrder($project, $validated['parent_id'] ?? null),
        ]);

        $task->resources()->sync($validated['resource_ids'] ?? []);

        return redirect()
            ->route('projects.tasks.index', $project)
            ->with('success', __('tasks.created', ['name' => $task->name]));
    }

    public function edit(Project $project, Task $task): View
    {
        $this->ensureBelongsTo($project, $task);

        [$tasks] = $this->taskTree($project);

        $descendantIds = $this->descendantIds($tasks, (int) $task->id);

        $candidates = $tasks
            ->reject(fn (Task $candidate): bool => $candidate->id === $task->id || in_array($candidate->id, $descendantIds, true))
            ->values();

        $resources = $project->resources()->orderBy('name')->get();
        $assignedIds = $task->resources()->pluck('resources.id')->all();

        $dependencies = Dependency::where('project_id', $project->id)
            ->where('successor_id', $task->id)
            ->with('predecessor:id,name')
            ->get();

        return view('tasks.edit', compact(
            'project',
            'task',
            'tasks',
            'candidates',
            'resources',
            'assignedIds',
            'dependencies',
        ));
    }

    public function update(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->ensureBelongsTo($project, $task);

        $validated = $this->validateTask($request, $project, $task);

        $task->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'duration' => $validated['duration'],
            'percent_complete' => $validated['percent_complete'] ?? 0,
            'priority' => $validated['priority'],
        ]);

        $task->resources()->sync($validated['resource_ids'] ?? []);

        return redirect()
            ->route('projects.tasks.index', $project)
            ->with('success', __('tasks.updated', ['name' => $task->name]));
    }

    public function destroy(Project $project, Task $task): RedirectResponse
    {
        $this->ensureBelongsTo($project, $task);

        $subtaskCount = $task->children()->count();
        $name = $task->name;
        $task->delete();

        return redirect()
            ->route('projects.tasks.index', $project)
            ->with(
                'success',
                $subtaskCount > 0
                    ? __('tasks.deleted_with_children', ['name' => $name, 'count' => $subtaskCount])
                    : __('tasks.deleted', ['name' => $name])
            );
    }

    /**
     * Pindahkan task satu posisi ke atas atau ke bawah di antara sibling (TSK-2).
     */
    public function move(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->ensureBelongsTo($project, $task);

        $validated = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $siblings = $project->tasks()
            ->where('parent_id', $task->parent_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (Task $sibling): bool => $sibling->id === $task->id);
        $targetIndex = $validated['direction'] === 'up' ? $index - 1 : $index + 1;

        if ($targetIndex < 0 || $targetIndex >= $siblings->count()) {
            $blockedKey = $validated['direction'] === 'up' ? 'move_blocked_first' : 'move_blocked_last';

            return redirect()
                ->route('projects.tasks.index', $project)
                ->with('error', __("tasks.{$blockedKey}", ['name' => $task->name]));
        }

        $this->swapSortOrder($task, $siblings[$targetIndex]);

        return redirect()
            ->route('projects.tasks.index', $project)
            ->with('success', __('tasks.moved_'.($validated['direction'] === 'up' ? 'up' : 'down'), ['name' => $task->name]));
    }

    /**
     * Jadikan task sebagai sub-kegiatan dari sibling sebelumnya (TSK-7).
     */
    public function indent(Project $project, Task $task): RedirectResponse
    {
        $this->ensureBelongsTo($project, $task);

        $siblings = $project->tasks()
            ->where('parent_id', $task->parent_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (Task $sibling): bool => $sibling->id === $task->id);

        if ($index === false || $index === 0) {
            return redirect()
                ->route('projects.tasks.index', $project)
                ->with('error', __('tasks.indent_blocked', ['name' => $task->name]));
        }

        $newParent = $siblings[$index - 1];

        $task->update([
            'parent_id' => $newParent->id,
            'sort_order' => $this->nextSortOrder($project, (int) $newParent->id),
        ]);

        return redirect()
            ->route('projects.tasks.index', $project)
            ->with('success', __('tasks.indented', ['name' => $task->name, 'parent' => $newParent->name]));
    }

    /**
     * Promosikan task satu tingkat menjadi tugas induk (TSK-7).
     */
    public function outdent(Project $project, Task $task): RedirectResponse
    {
        $this->ensureBelongsTo($project, $task);

        if ($task->parent_id === null) {
            return redirect()
                ->route('projects.tasks.index', $project)
                ->with('error', __('tasks.outdent_blocked', ['name' => $task->name]));
        }

        $parent = $project->tasks()->find($task->parent_id);

        if ($parent === null) {
            $task->update(['parent_id' => null, 'sort_order' => $this->nextSortOrder($project, null)]);

            return redirect()
                ->route('projects.tasks.index', $project)
                ->with('success', __('tasks.outdented', ['name' => $task->name]));
        }

        $task->update([
            'parent_id' => $parent->parent_id,
            'sort_order' => $this->nextSortOrder($project, $parent->parent_id !== null ? (int) $parent->parent_id : null),
        ]);

        return redirect()
            ->route('projects.tasks.index', $project)
            ->with('success', __('tasks.outdented', ['name' => $task->name]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTask(Request $request, Project $project, ?Task $task = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'duration' => ['required', 'integer', 'min:0', 'max:999'],
            'percent_complete' => ['nullable', 'integer', 'between:0,100'],
            'priority' => ['required', 'string', 'in:low,medium,high,critical'],
            'resource_ids' => ['nullable', 'array'],
            'resource_ids.*' => ['integer'],
        ], [
            'duration.min' => __('tasks.validation_duration_min'),
            'duration.max' => __('tasks.validation_duration_max'),
            'percent_complete.between' => __('tasks.validation_percent_range'),
        ]);

        if (! empty($validated['parent_id'])) {
            $parent = $project->tasks()->find($validated['parent_id']);

            if ($parent === null) {
                throw ValidationException::withMessages(['parent_id' => __('tasks.validation_parent_missing')]);
            }

            if ($task !== null) {
                [$allTasks] = $this->taskTree($project);
                $descendants = $this->descendantIds($allTasks, (int) $task->id);

                if ($parent->id === $task->id || in_array($parent->id, $descendants, true)) {
                    throw ValidationException::withMessages([
                        'parent_id' => __('tasks.validation_parent_cycle'),
                    ]);
                }
            }

            $validated['parent_id'] = (int) $validated['parent_id'];
        } else {
            unset($validated['parent_id']);
        }

        if (! empty($validated['resource_ids'])) {
            $validIds = $project->resources()->pluck('id')->all();
            $validated['resource_ids'] = array_values(array_intersect($validated['resource_ids'], $validIds));
        } else {
            unset($validated['resource_ids']);
        }

        if (isset($validated['start_date']) && $validated['start_date'] === '') {
            $validated['start_date'] = null;
        }

        if (isset($validated['description']) && $validated['description'] === '') {
            $validated['description'] = null;
        }

        $validated['percent_complete'] = max(0, min(100, (int) ($validated['percent_complete'] ?? 0)));

        return $validated;
    }

    /**
     * @return array{0: Collection<int, Task>, 1: array<int|string, list<Task>|Collection<int, Task>>}
     */
    private function taskTree(Project $project): array
    {
        $tasks = $project->tasks()
            ->with('resources:id,name,contact')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $childrenMap = [];
        $roots = [];

        foreach ($tasks as $task) {
            if ($task->parent_id === null) {
                $roots[] = $task;

                continue;
            }

            $childrenMap[$task->parent_id][] = $task;
        }

        return [$tasks, $childrenMap + [0 => collect($roots)]];
    }

    /**
     * Saring pohon task agar hanya menyisakan tugas milik satu anggota
     * beserta leluhurnya agar hierarki tetap terlihat (ASN-5).
     *
     * @param  Collection<int, Task>  $tasks
     * @param  array<int|string, list<Task>|Collection<int, Task>>  $childrenMap
     * @return array{0: Collection<int, Task>, 1: array<int|string, list<Task>|Collection<int, Task>>}
     */
    private function filterTreeByMember(Collection $tasks, array $childrenMap, int $memberId): array
    {
        $assignedIds = $tasks
            ->filter(fn (Task $task): bool => $task->resources->contains('id', $memberId))
            ->map(fn (Task $task): int => (int) $task->id)
            ->all();

        if ($assignedIds === []) {
            return [collect(), [0 => collect()]];
        }

        $parentOf = [];
        $byId = [];

        foreach ($tasks as $task) {
            $byId[(int) $task->id] = $task;
            $parentOf[(int) $task->id] = $task->parent_id !== null ? (int) $task->parent_id : null;
        }

        $keep = [];

        foreach ($assignedIds as $id) {
            $keep[$id] = true;

            $current = $parentOf[$id] ?? null;

            while ($current !== null) {
                $keep[$current] = true;
                $current = $parentOf[$current] ?? null;
            }
        }

        $filtered = $tasks->filter(fn (Task $task): bool => isset($keep[(int) $task->id]))->values();

        $filteredChildren = [];
        $filteredRoots = [];

        foreach ($filtered as $task) {
            if ($task->parent_id !== null && isset($keep[(int) $task->parent_id])) {
                $filteredChildren[$task->parent_id][] = $task;

                continue;
            }

            $filteredRoots[] = $task;
        }

        return [$filtered, $filteredChildren + [0 => collect($filteredRoots)]];
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return list<int>
     */
    private function descendantIds(Collection $tasks, int $taskId): array
    {
        $childrenMap = [];

        foreach ($tasks as $task) {
            if ($task->parent_id !== null) {
                $childrenMap[$task->parent_id][] = (int) $task->id;
            }
        }

        $descendants = [];
        $queue = $childrenMap[$taskId] ?? [];

        while ($queue !== []) {
            $current = array_pop($queue);
            $descendants[] = $current;
            $queue = array_merge($queue, $childrenMap[$current] ?? []);
        }

        return $descendants;
    }

    private function nextSortOrder(Project $project, ?int $parentId): int
    {
        $query = $project->tasks()->where('parent_id', $parentId);

        return (int) $query->max('sort_order') + 1;
    }

    private function swapSortOrder(Task $first, Task $second): void
    {
        $firstOrder = (int) $first->sort_order;
        $secondOrder = (int) $second->sort_order;

        // Bila sort_order sama (mis. hasil seed), gunakan posisi relatif sebagai pengganti.
        if ($firstOrder === $secondOrder) {
            $firstOrder = $first->id;
            $secondOrder = $second->id;
        }

        $first->update(['sort_order' => $secondOrder]);
        $second->update(['sort_order' => $firstOrder]);
    }

    private function ensureBelongsTo(Project $project, Task $task): void
    {
        abort_if($task->project_id !== $project->id, 404);
    }

    private function schedule(Project $project): ?ScheduleResult
    {
        try {
            return ScheduleCalculator::forProject($project)->calculate($project);
        } catch (CircularDependencyException $exception) {
            return null;
        }
    }

    /**
     * Peringatan cuti (FR-23) dan tugas tumpang tindih (FR-24).
     *
     * @return list<string>
     */
    private function resourceWarnings(Project $project, ?ScheduleResult $schedule): array
    {
        if ($schedule === null) {
            return [];
        }

        $warnings = [];

        foreach ($project->resources()->with('leaves')->get() as $resource) {
            $assigned = $project->tasks()
                ->whereHas('resources', fn ($query) => $query->where('resources.id', $resource->id))
                ->get();

            $spans = [];

            foreach ($assigned as $task) {
                $taskSchedule = $schedule->task((int) $task->id);

                if ($taskSchedule === null) {
                    continue;
                }

                $spans[] = ['task' => $task, 'start' => $taskSchedule->start, 'finish' => $taskSchedule->finish];

                foreach ($resource->leaves as $leave) {
                    $overlaps = $taskSchedule->start->lte($leave->end_date)
                        && $taskSchedule->finish->gte($leave->start_date);

                    if ($overlaps) {
                        $warnings[] = "{$resource->name} sedang cuti ({$leave->start_date->format('d M')} – {$leave->end_date->format('d M')}) "
                            ."padahal ditugaskan pada \"{$task->name}\" ({$taskSchedule->start->format('d M')} – {$taskSchedule->finish->format('d M')}).";
                    }
                }
            }

            usort($spans, fn (array $first, array $second): int => $first['start']->timestamp <=> $second['start']->timestamp);

            for ($index = 1; $index < count($spans); $index++) {
                $previous = $spans[$index - 1];
                $current = $spans[$index];

                if ($current['start']->lte($previous['finish'])) {
                    $warnings[] = "{$resource->name} ditugaskan pada tugas yang tumpang tindih: "
                        ."\"{$previous['task']->name}\" dan \"{$current['task']->name}\".";
                }
            }
        }

        return array_values(array_unique($warnings));
    }
}
