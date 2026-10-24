<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Schedule\CircularDependencyException;
use App\Services\ScheduleCalculator;
use App\Services\WorkCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisualizationController extends Controller
{
    public function gantt(Project $project, Request $request): View|RedirectResponse
    {
        [$data, $error] = $this->payload($project);

        if ($data === null) {
            return redirect()
                ->route('projects.tasks.index', $project)
                ->with('error', $error ?? 'Jadwal tidak dapat dihitung.');
        }

        $resources = $project->resources()->orderBy('name')->get();

        // Filter per anggota (ASN-5).
        $memberId = $request->query('member') !== null ? (int) $request->query('member') : null;

        if ($memberId !== null && ! $resources->contains('id', $memberId)) {
            $memberId = null;
        }

        if ($memberId !== null) {
            $data = $this->filterForMember($data, $memberId);
        }

        return view('visualizations.gantt', compact('project', 'data', 'resources', 'memberId'));
    }

    public function pert(Project $project): View|RedirectResponse
    {
        [$data, $error] = $this->payload($project, withCalendar: false);

        if ($data === null) {
            return redirect()
                ->route('projects.tasks.index', $project)
                ->with('error', $error ?? 'Jadwal tidak dapat dihitung.');
        }

        return view('visualizations.pert', compact('project', 'data'));
    }

    /**
     * Menyiapkan data terjadwal yang selalu sinkron dengan data tugas (FR-31),
     * lengkap dengan label bahasa untuk renderer JS dan penanda konflik cuti.
     *
     * @return array{0: array<string, mixed>|null, 1: string|null}
     */
    private function payload(Project $project, bool $withCalendar = true): array
    {
        try {
            $schedule = ScheduleCalculator::forProject($project)->calculate($project);
        } catch (CircularDependencyException $exception) {
            return [null, $exception->getMessage()];
        }

        $data = $schedule->toArray();
        $data['labels'] = $this->jsLabels();

        // Penanda konflik cuti (⏰) per tugas untuk legend & bar Gantt.
        $conflictIds = $this->leaveConflictTaskIds($project, $schedule);

        foreach ($data['tasks'] as $index => $task) {
            $data['tasks'][$index]['leave_conflict'] = in_array($task['id'], $conflictIds, true);
        }

        if (! $withCalendar) {
            return [$data, null];
        }

        $calendar = WorkCalendar::forProject($project);
        $from = $schedule->projectStart->copy()->subDays(3);
        $to = $schedule->projectFinish->copy()->addDays(10);

        $data['from'] = $from->format('Y-m-d');
        $data['to'] = $to->format('Y-m-d');
        $data['today'] = now()->format('Y-m-d');
        $data['days'] = $calendar->daysBetween($from, $to);

        return [$data, null];
    }

    /**
     * Label terlokalisasi untuk renderer Gantt/PERT (PRD §10).
     *
     * @return array<string, mixed>
     */
    private function jsLabels(): array
    {
        return [
            'task_name' => __('viz.js_task_name'),
            'empty' => __('viz.js_empty'),
            'pert_empty' => __('viz.js_pert_empty'),
            'critical' => __('viz.js_critical'),
            'critical_short' => __('viz.js_critical_short'),
            'milestone' => __('viz.js_milestone'),
            'percent' => __('viz.js_percent'),
            'schedule' => __('viz.js_schedule'),
            'duration' => __('viz.js_duration_days'),
            'slack' => __('viz.js_slack_days'),
            'assignees' => __('viz.js_assignees'),
            'unassigned' => __('viz.js_unassigned'),
            'conflict' => __('viz.js_conflict'),
            'detail_title' => __('viz.js_detail_title'),
            'detail_close' => __('viz.js_detail_close'),
            'detail_predecessors' => __('viz.js_detail_predecessors'),
            'detail_none' => __('viz.js_detail_none'),
            'months' => __('viz.js_months'),
            'weekdays' => __('viz.js_weekdays'),
        ];
    }

    /**
     * ID tugas yang jadwalnya menabrak cuti salah satu penanggung jawabnya (⏰).
     *
     * @return list<int>
     */
    private function leaveConflictTaskIds(Project $project, $schedule): array
    {
        $conflicts = [];

        foreach ($project->resources()->with('leaves')->get() as $resource) {
            if ($resource->leaves->isEmpty()) {
                continue;
            }

            $assignedIds = $resource->tasks()->pluck('tasks.id')->all();

            foreach ($assignedIds as $taskId) {
                $taskSchedule = $schedule->task((int) $taskId);

                if ($taskSchedule === null) {
                    continue;
                }

                foreach ($resource->leaves as $leave) {
                    if ($taskSchedule->start->lte($leave->end_date) && $taskSchedule->finish->gte($leave->start_date)) {
                        $conflicts[] = (int) $taskId;

                        break;
                    }
                }
            }
        }

        return array_values(array_unique($conflicts));
    }

    /**
     * Saring task & edge agar hanya menyisakan tugas milik satu anggota
     * beserta leluhurnya (ASN-5).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function filterForMember(array $data, int $memberId): array
    {
        $keep = [];

        foreach ($data['tasks'] as $task) {
            foreach ($task['assignees'] as $assignee) {
                if ((int) $assignee['id'] === $memberId) {
                    $keep[(int) $task['id']] = true;
                }
            }
        }

        if ($keep === []) {
            $data['tasks'] = [];
            $data['edges'] = [];

            return $data;
        }

        $parentOf = [];

        foreach ($data['tasks'] as $task) {
            $parentOf[(int) $task['id']] = $task['parent_id'] !== null ? (int) $task['parent_id'] : null;
        }

        foreach (array_keys($keep) as $id) {
            $current = $parentOf[$id] ?? null;

            while ($current !== null && ! isset($keep[$current])) {
                $keep[$current] = true;
                $current = $parentOf[$current] ?? null;
            }
        }

        $data['tasks'] = array_values(array_filter(
            $data['tasks'],
            fn (array $task): bool => isset($keep[(int) $task['id']])
        ));

        $data['edges'] = array_values(array_filter(
            $data['edges'],
            fn (array $edge): bool => isset($keep[(int) $edge['predecessor_id']]) && isset($keep[(int) $edge['successor_id']])
        ));

        return $data;
    }
}
