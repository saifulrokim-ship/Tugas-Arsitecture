<?php

namespace App\Services;

use App\Models\Dependency;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Menyimpan dan memuat seluruh isi proyek sebagai satu file lokal (JSON).
 * Meniru alur kerja single-user GanttProject: simpan proyek ke file, buka lagi nanti.
 */
class ProjectFileService
{
    public const FORMAT = 'proyekta-project';

    public const VERSION = 1;

    /**
     * Serialisasi seluruh isi proyek ke array siap-ekspor.
     *
     * @return array<string, mixed>
     */
    public function export(Project $project): array
    {
        $project->load([
            'calendar',
            'holidays' => fn ($query) => $query->orderBy('date'),
            'tasks' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'dependencies',
            'resources' => fn ($query) => $query->orderBy('id'),
            'resources.leaves' => fn ($query) => $query->orderBy('start_date'),
        ]);

        $tasks = $project->tasks->map(fn (Task $task): array => [
            'id' => $task->id,
            'parent_id' => $task->parent_id,
            'name' => $task->name,
            'start_date' => $task->start_date?->format('Y-m-d'),
            'duration' => $task->duration,
            'priority' => $task->priority,
            'sort_order' => $task->sort_order,
        ])->values()->all();

        $assignments = DB::table('task_assignments')
            ->join('tasks', 'tasks.id', '=', 'task_assignments.task_id')
            ->where('tasks.project_id', $project->id)
            ->orderBy('task_assignments.task_id')
            ->orderBy('task_assignments.resource_id')
            ->get(['task_assignments.task_id', 'task_assignments.resource_id'])
            ->map(fn ($row): array => ['task_id' => $row->task_id, 'resource_id' => $row->resource_id])
            ->values()
            ->all();

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'project' => [
                'name' => $project->name,
                'organization' => $project->organization,
                'description' => $project->description,
                'start_date' => $project->start_date->format('Y-m-d'),
            ],
            'calendar' => $project->calendar ? [
                'workdays' => $project->calendar->workdays,
                'weekend_days' => $project->calendar->weekend_days,
            ] : null,
            'holidays' => $project->holidays->map(fn (Holiday $holiday): array => [
                'date' => $holiday->date->format('Y-m-d'),
                'description' => $holiday->description,
            ])->values()->all(),
            'tasks' => $tasks,
            'dependencies' => $project->dependencies->map(fn (Dependency $dependency): array => [
                'predecessor_id' => $dependency->predecessor_id,
                'successor_id' => $dependency->successor_id,
                'type' => $dependency->type,
                'lag' => $dependency->lag,
            ])->values()->all(),
            'resources' => $project->resources->map(fn (Resource $resource): array => [
                'id' => $resource->id,
                'name' => $resource->name,
                'contact' => $resource->contact,
                'role' => $resource->role,
                'salary' => $resource->salary,
                'leaves' => $resource->leaves->map(fn ($leave): array => [
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'description' => $leave->description,
                ])->values()->all(),
            ])->values()->all(),
            'assignments' => $assignments,
        ];
    }

    /**
     * Ekspor sebagai JSON siap unduh.
     */
    public function toJson(Project $project): string
    {
        return json_encode($this->export($project), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Bangun ulang proyek lengkap dari isi file.
     *
     * @throws InvalidArgumentException bila file bukan ekspor Proyekta yang sah
     */
    public function import(array $data): Project
    {
        $this->assertValid($data);

        return DB::transaction(function () use ($data): Project {
            $project = Project::create($data['project']);

            $calendar = $data['calendar'] ?? null;
            $project->calendar()->create(is_array($calendar) ? [
                'workdays' => $calendar['workdays'] ?? WorkCalendar::DEFAULT_WORKDAYS,
                'weekend_days' => $calendar['weekend_days'] ?? WorkCalendar::DEFAULT_WEEKEND_DAYS,
            ] : [
                'workdays' => WorkCalendar::DEFAULT_WORKDAYS,
                'weekend_days' => WorkCalendar::DEFAULT_WEEKEND_DAYS,
            ]);

            foreach ($data['holidays'] ?? [] as $holiday) {
                $project->holidays()->create([
                    'date' => $holiday['date'],
                    'description' => $holiday['description'] ?? null,
                ]);
            }

            // Tugas dibuat tanpa induk dulu agar urutan file tidak berpengaruh,
            // lalu parent_id dipetakan ke ID baru pada putaran kedua.
            $taskIdMap = [];
            $pendingParents = [];

            foreach ($data['tasks'] ?? [] as $task) {
                $newTask = $project->tasks()->create([
                    'name' => $task['name'],
                    'start_date' => $task['start_date'] ?? null,
                    'duration' => $task['duration'] ?? 1,
                    'priority' => $task['priority'] ?? 'medium',
                    'sort_order' => $task['sort_order'] ?? 0,
                ]);
                $taskIdMap[$task['id']] = $newTask->id;
                $pendingParents[$newTask->id] = $task['parent_id'] ?? null;
            }

            foreach ($pendingParents as $newId => $oldParentId) {
                if ($oldParentId !== null && isset($taskIdMap[$oldParentId])) {
                    Task::query()->whereKey($newId)->update(['parent_id' => $taskIdMap[$oldParentId]]);
                }
            }

            foreach ($data['dependencies'] ?? [] as $dependency) {
                $predecessorId = $taskIdMap[$dependency['predecessor_id']] ?? null;
                $successorId = $taskIdMap[$dependency['successor_id']] ?? null;

                if ($predecessorId === null || $successorId === null) {
                    continue;
                }

                $project->dependencies()->create([
                    'predecessor_id' => $predecessorId,
                    'successor_id' => $successorId,
                    'type' => in_array($dependency['type'] ?? 'FS', ['FS', 'SS'], true) ? $dependency['type'] : 'FS',
                    'lag' => max(0, (int) ($dependency['lag'] ?? 0)),
                ]);
            }

            $resourceIdMap = [];

            foreach ($data['resources'] ?? [] as $resource) {
                $newResource = $project->resources()->create([
                    'name' => $resource['name'],
                    'contact' => $resource['contact'] ?? null,
                    'role' => $resource['role'] ?? null,
                    'salary' => $resource['salary'] ?? null,
                ]);
                $resourceIdMap[$resource['id']] = $newResource->id;

                foreach ($resource['leaves'] ?? [] as $leave) {
                    $newResource->leaves()->create([
                        'start_date' => $leave['start_date'],
                        'end_date' => $leave['end_date'],
                        'description' => $leave['description'] ?? null,
                    ]);
                }
            }

            foreach ($data['assignments'] ?? [] as $assignment) {
                $taskId = $taskIdMap[$assignment['task_id']] ?? null;
                $resourceId = $resourceIdMap[$assignment['resource_id']] ?? null;

                if ($taskId === null || $resourceId === null) {
                    continue;
                }

                DB::table('task_assignments')->insertOrIgnore([
                    'task_id' => $taskId,
                    'resource_id' => $resourceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $project;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertValid(array $data): void
    {
        if (($data['format'] ?? null) !== self::FORMAT) {
            throw new InvalidArgumentException('File bukan ekspor Proyekta. Pilih file berekstensi .json yang dihasilkan oleh tombol "Simpan ke File".');
        }

        if ((int) ($data['version'] ?? 0) > self::VERSION) {
            throw new InvalidArgumentException('Versi file '.($data['version'] ?? '?').' lebih baru daripada yang didukung aplikasi ini.');
        }

        $project = $data['project'] ?? null;

        if (! is_array($project) || trim((string) ($project['name'] ?? '')) === '') {
            throw new InvalidArgumentException('File rusak: field "project.name" tidak ditemukan.');
        }

        if (($project['start_date'] ?? null) === null) {
            throw new InvalidArgumentException('File rusak: field "project.start_date" tidak ditemukan.');
        }

        foreach (['tasks', 'dependencies', 'resources', 'holidays', 'assignments'] as $collection) {
            if (isset($data[$collection]) && ! is_array($data[$collection])) {
                throw new InvalidArgumentException("File rusak: field \"{$collection}\" harus berupa daftar.");
            }
        }
    }
}
