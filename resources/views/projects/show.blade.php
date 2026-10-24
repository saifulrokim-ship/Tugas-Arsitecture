@extends('layouts.app')

@section('title', $project->name)

@section('subtitle', ($project->organization ? $project->organization.' · ' : '').__('projects.subtitle_start', ['date' => $project->start_date->format('d M Y')]))

@section('actions')
    <a href="{{ route('projects.export', $project) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100" title="{{ __('projects.save_to_file_title') }}">{{ __('projects.save_to_file') }}</a>
    <a href="{{ route('projects.gantt', $project) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Gantt</a>
    <a href="{{ route('projects.pert', $project) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">PERT</a>
        <a href="{{ route('projects.edit', $project) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('common.edit') }}</a>
        <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('{{ e(__('projects.delete_confirm_summary')) }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">{{ __('common.delete') }}</button>
        </form>
@endsection

@section('content')
    @if ($scheduleError)
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">{{ __('projects.schedule_error_title') }}</p>
            <p class="mt-1">{{ $scheduleError }}</p>
            <a href="{{ route('projects.tasks.index', $project) }}" class="mt-2 inline-block font-semibold underline">{{ __('projects.schedule_error_fix') }}</a>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('projects.total_duration') }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $schedule?->projectDuration ?? '—' }} <span class="text-sm font-medium text-slate-500">{{ __('projects.workdays_unit') }}</span></p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('projects.planned_start') }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $schedule?->projectStart->format('d M Y') ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('projects.predicted_finish') }}</p>
            <p class="mt-2 text-2xl font-semibold {{ $schedule?->projectFinish->isFuture() ? 'text-indigo-600' : 'text-slate-900' }}">
                {{ $schedule?->projectFinish->format('d M Y') ?? '—' }}
            </p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('projects.critical_tasks') }}</p>
            <p class="mt-2 text-2xl font-semibold text-rose-600">{{ $schedule ? count($schedule->criticalTaskIds()) : '—' }}
                <span class="text-sm font-medium text-slate-500">/ {{ $project->tasks_count ?? $project->tasks()->count() }} {{ __('tasks.stat_main_tasks') }}</span>
            </p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-1">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('projects.detail_heading') }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">{{ __('projects.detail_organization') }}</dt>
                    <dd class="font-medium text-slate-800">{{ $project->organization ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('projects.detail_description') }}</dt>
                    <dd class="whitespace-pre-line text-slate-800">{{ $project->description ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('projects.detail_team') }}</dt>
                    <dd class="font-medium text-slate-800">{{ $project->resources()->count() }} {{ __('projects.people_unit') }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('projects.detail_holidays') }}</dt>
                    <dd class="font-medium text-slate-800">{{ $project->holidays->count() }} {{ __('projects.days_unit') }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('projects.detail_calendar') }}</dt>
                    <dd class="font-medium text-slate-800">
                        @if ($project->calendar)
                            {{ collect($project->calendar->workdays)->map(fn ($day) => \Illuminate\Support\Arr::wrap(__('calendar.day_names'))[$day - 1] ?? $day)->implode(', ') }}
                            <span class="text-slate-500">({{ __('calendar.weekend_summary', ['days' => collect($project->calendar->weekend_days)->map(fn ($day) => \Illuminate\Support\Arr::wrap(__('calendar.day_names'))[$day - 1] ?? $day)->implode(', ') ?: '—']) }})</span>
                        @else
                            {{ __('projects.calendar_default') }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('projects.critical_heading') }}</h2>
                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600">{{ __('projects.critical_slack_zero') }}</span>
            </div>

            @if ($schedule === null)
                <p class="mt-4 text-sm text-slate-500">—</p>
            @else
                @php
                    $criticalTasks = collect($schedule->orderedTaskIds())
                        ->map(fn (int $taskId) => $schedule->task($taskId))
                        ->filter(fn ($task) => $task !== null && $task->critical)
                        ->values();
                @endphp

                @if ($criticalTasks->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">{{ __('projects.critical_empty_no_tasks') }}</p>
                @else
                    <ol class="mt-4 space-y-2">
                        @foreach ($criticalTasks as $task)
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-rose-100 bg-rose-50/60 px-3 py-2">
                                <span class="text-sm font-medium text-slate-800">
                                    <span class="mr-2 inline-block rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">⚠ {{ __('tasks.badge_critical') }}</span>
                                    {{ $task->name }}
                                    @if ($task->isSummary)
                                        <span class="text-xs text-slate-500">{{ __('projects.critical_summary_tag') }}</span>
                                    @endif
                                </span>
                                <span class="text-xs text-slate-600">
                                    {{ $task->start->format('d M') }} → {{ $task->finish->format('d M') }}
                                    · {{ __('tasks.duration_days', ['count' => $task->duration]) }}
                                    · slack {{ $task->slack }}
                                </span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-4 text-xs text-slate-500">
                        {!! __('projects.critical_note', ['link' => '<a href="'.route('projects.pert', $project).'" class="font-semibold text-indigo-600 underline">'.__('projects.pert_link_label').'</a>']) !!}
                    </p>
                @endif
            @endif
        </div>
    </div>
@endsection
