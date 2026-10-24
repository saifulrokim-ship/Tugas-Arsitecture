@extends('layouts.app')

@section('title', __('tasks.index_title'))

@section('subtitle', __('tasks.index_subtitle'))

@section('actions')
        <a href="{{ route('projects.tasks.create', $project) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            {{ __('tasks.add_task') }}
        </a>
    <a href="{{ route('projects.gantt', $project) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Gantt</a>
@endsection

@section('content')
    @if ($schedule === null)
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">{{ __('tasks.circular_title') }}</p>
            <p class="mt-1">{{ __('tasks.circular_body') }}</p>
        </div>
    @endif

    @if ($warnings !== [])
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="mb-1 font-semibold">{{ __('tasks.warnings_title') }}</p>
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">{{ __('tasks.list_heading') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $tasks->where('parent_id', null)->count() }} {{ __('tasks.stat_main_tasks') }} ·
                    {{ $tasks->where('parent_id', '!=', null)->count() }} {{ __('tasks.stat_subtasks') }} ·
                    {{ $schedule ? count($schedule->criticalTaskIds()) : 0 }} {{ __('tasks.stat_critical') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Filter per anggota (ASN-5). --}}
                @if ($resources->isNotEmpty())
                    <form method="GET" action="{{ route('projects.tasks.index', $project) }}" class="flex items-center gap-2">
                        <label for="member-filter" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('tasks.filter_label') }}</label>
                        <select name="member" id="member-filter" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('tasks.filter_all') }}</option>
                            @foreach ($resources as $resource)
                                <option value="{{ $resource->id }}" @selected($memberId === (int) $resource->id)>{{ $resource->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <p class="text-xs text-slate-500">{{ __('tasks.recalc_note') }}</p>
            </div>
        </div>

        @if ($tasks->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-slate-500">{{ $memberId !== null ? __('tasks.filter_hint') : __('tasks.empty') }}</p>
                    <a href="{{ route('projects.tasks.create', $project) }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('tasks.add_first_task') }}</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-slate-200 text-sm"
                    role="grid"
                    aria-label="{{ __('tasks.list_heading') }}"
                    data-task-grid
                >
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">{{ __('tasks.col_task') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('tasks.col_schedule') }}</th>
                            <th scope="col" class="px-4 py-3 text-center">{{ __('tasks.col_duration') }}</th>
                            <th scope="col" class="px-4 py-3 text-center">{{ __('tasks.col_slack') }}</th>
                            <th scope="col" class="px-4 py-3 text-center">{{ __('tasks.col_percent') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('tasks.col_assignees') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('tasks.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($childrenMap[0] as $rootTask)
                            @include('tasks._row', [
                                'task' => $rootTask,
                                'depth' => 0,
                                'project' => $project,
                                'childrenMap' => $childrenMap,
                                'schedule' => $schedule,
                                'predecessors' => $predecessors,
                            ])
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">{{ __('tasks.keyboard_hint') }}</p>
        @endif
    </div>
@endsection
