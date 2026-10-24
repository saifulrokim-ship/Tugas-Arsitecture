@php
    $taskSchedule = $schedule?->task((int) $task->id);
    $childTasks = $childrenMap[$task->id] ?? [];
    $isSummary = count($childTasks) > 0;
    $isMilestone = ! $isSummary && (int) $task->duration === 0;
    $priorityStyles = [
        'low' => 'bg-slate-100 text-slate-600',
        'medium' => 'bg-amber-100 text-amber-700',
        'high' => 'bg-rose-100 text-rose-700',
        'critical' => 'bg-fuchsia-100 text-fuchsia-700',
    ];
    $priorityLabels = [
        'low' => __('tasks.priority_low'),
        'medium' => __('tasks.priority_medium'),
        'high' => __('tasks.priority_high'),
        'critical' => __('tasks.priority_critical'),
    ];
    $taskPredecessors = $predecessors->get($task->id, collect());
    $percent = max(0, min(100, (int) $task->percent_complete));
@endphp

<tr
    data-task-row
    data-task-id="{{ $task->id }}"
    data-parent-id="{{ $task->parent_id ?? '' }}"
    class="align-top hover:bg-slate-50 {{ $isSummary ? 'bg-slate-50/60' : '' }}"
>
    <td class="px-4 py-2.5" role="gridcell" tabindex="-1">
        <div class="flex items-start gap-2" style="padding-left: {{ $depth * 22 }}px">
            @if ($isSummary)
                <button
                    type="button"
                    class="js-task-toggle mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-[10px] leading-none text-slate-600 hover:bg-slate-100"
                    data-task-id="{{ $task->id }}"
                    aria-expanded="true"
                    aria-label="{{ __('tasks.toggle_subtasks') }}"
                >▾</button>
            @else
                <span class="mt-0.5 block h-5 w-5 shrink-0"></span>
            @endif

            <div class="min-w-0">
                <p class="flex flex-wrap items-center gap-2">
                    @if ($isMilestone)
                        <span class="text-xs text-indigo-600" title="{{ __('tasks.badge_milestone') }}" aria-label="{{ __('tasks.badge_milestone') }}">◆</span>
                    @endif
                    <span class="truncate font-medium text-slate-900">{{ $task->name }}</span>
                    <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase {{ $priorityStyles[$task->priority] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $priorityLabels[$task->priority] ?? $task->priority }}
                    </span>
                    @if ($taskSchedule?->critical)
                        <span class="rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white" title="{{ __('tasks.critical_title') }}">⚠ {{ __('tasks.badge_critical') }}</span>
                    @endif
                    @if ($isMilestone)
                        <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-indigo-700">{{ __('tasks.badge_milestone') }}</span>
                    @endif
                </p>
                <p class="mt-0.5 text-xs text-slate-500">
                    @if ($isSummary)
                        <span class="rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-600">{{ __('tasks.badge_parent') }}</span>
                        {{ __('tasks.subtask_count', ['count' => count($childTasks)]) }}
                    @elseif ($task->parent_id)
                        {{ __('tasks.type_subtask') }}
                    @else
                        {{ __('tasks.type_main_task') }}
                    @endif
                </p>
                @if ($task->description)
                    <p class="mt-0.5 max-w-md text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($task->description, 140) }}</p>
                @endif
                @if ($taskPredecessors->isNotEmpty())
                    <p class="mt-1 text-xs text-slate-500">
                        ← {{ $taskPredecessors->map(fn ($dependency) => $dependency->predecessor?->name.' ('.$dependency->label().')')->implode(', ') }}
                    </p>
                @endif
            </div>
        </div>
    </td>

    <td class="px-4 py-2.5 text-sm text-slate-700" role="gridcell" tabindex="-1">
        @if ($taskSchedule)
            {{ $taskSchedule->start->format('d M Y') }}<br>
            <span class="text-xs text-slate-500">{{ __('tasks.to_date', ['date' => $taskSchedule->finish->format('d M Y')]) }}</span>
        @else
            <span class="text-slate-400">—</span>
        @endif
    </td>

    <td class="px-4 py-2.5 text-center text-sm text-slate-700" role="gridcell" tabindex="-1">
        {{ __('tasks.duration_days', ['count' => $taskSchedule?->duration ?? $task->duration]) }}
    </td>

    <td class="px-4 py-2.5 text-center text-sm" role="gridcell" tabindex="-1">
        @if ($taskSchedule)
            @if ($taskSchedule->critical)
                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700" title="{{ __('tasks.critical_title') }}">⚠ {{ __('tasks.badge_critical') }}</span>
            @else
                <span class="text-slate-600" title="{{ __('tasks.slack_title', ['count' => $taskSchedule->slack]) }}">{{ __('tasks.slack_days', ['count' => $taskSchedule->slack]) }}</span>
            @endif
        @else
            <span class="text-slate-400">—</span>
        @endif
    </td>

    {{-- Percent complete dengan progress bar + ARIA (TSK-6). --}}
    <td class="px-4 py-2.5 text-center text-sm text-slate-700" role="gridcell" tabindex="-1">
        <div class="flex items-center justify-center gap-2">
            <div
                class="h-2 w-20 overflow-hidden rounded-full bg-slate-200"
                role="progressbar"
                aria-valuenow="{{ $percent }}"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="{{ __('tasks.progress_label', ['percent' => $percent]) }}"
            >
                <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $percent }}%"></div>
            </div>
            <span class="w-9 text-right text-xs text-slate-500">{{ $percent }}%</span>
        </div>
    </td>

    <td class="px-4 py-2.5 text-sm text-slate-700" role="gridcell" tabindex="-1">
        @php $assigneeNames = $task->resources->pluck('name'); @endphp
        @if ($assigneeNames->isEmpty())
            <span class="text-slate-400">{{ __('tasks.unassigned') }}</span>
        @else
            {{ $assigneeNames->implode(', ') }}
        @endif
    </td>

    <td class="px-4 py-2.5">
        <div class="flex items-center justify-end gap-1.5">
                {{-- Pindah urutan (TSK-2) & indent/outdent (TSK-7). --}}
                <form method="POST" action="{{ route('projects.tasks.move', [$project, $task]) }}">
                    @csrf
                    <input type="hidden" name="direction" value="up">
                    <button type="submit" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100" aria-label="{{ __('tasks.action_move_up') }}" title="{{ __('tasks.action_move_up') }}">↑</button>
                </form>
                <form method="POST" action="{{ route('projects.tasks.move', [$project, $task]) }}">
                    @csrf
                    <input type="hidden" name="direction" value="down">
                    <button type="submit" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100" aria-label="{{ __('tasks.action_move_down') }}" title="{{ __('tasks.action_move_down') }}">↓</button>
                </form>
                <form method="POST" action="{{ route('projects.tasks.indent', [$project, $task]) }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100" aria-label="{{ __('tasks.action_indent') }}" title="{{ __('tasks.action_indent') }}">→</button>
                </form>
                <form method="POST" action="{{ route('projects.tasks.outdent', [$project, $task]) }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100" aria-label="{{ __('tasks.action_outdent') }}" title="{{ __('tasks.action_outdent') }}" {{ $task->parent_id === null ? 'disabled' : '' }}>←</button>
                </form>
                <a href="{{ route('projects.tasks.create', [$project, 'parent' => $task->id]) }}" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100" title="{{ __('tasks.action_add_sub_title') }}" aria-label="{{ __('tasks.action_add_sub_title') }}">{{ __('tasks.action_add_sub') }}</a>
                <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('common.edit') }}</a>
                <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" data-task-delete-form onsubmit="return confirm('{{ e($isSummary ? __('tasks.delete_confirm_summary', ['name' => $task->name]) : __('tasks.delete_confirm', ['name' => $task->name])) }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">{{ __('common.delete') }}</button>
                </form>
        </div>
    </td>
</tr>

@foreach ($childTasks as $childTask)
    @include('tasks._row', [
        'task' => $childTask,
        'depth' => $depth + 1,
        'project' => $project,
        'childrenMap' => $childrenMap,
        'schedule' => $schedule,
        'predecessors' => $predecessors,
    ])
@endforeach
