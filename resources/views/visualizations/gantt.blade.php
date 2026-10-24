@extends('layouts.app')

@section('title', __('viz.gantt_title'))

@section('subtitle', __('viz.gantt_subtitle'))

@section('actions')
    <a href="{{ route('projects.pert', $project) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('viz.gantt_view_pert') }}</a>
    <a href="{{ route('projects.tasks.index', $project) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('viz.gantt_view_tasks') }}</a>
@endsection

@section('content')
    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('viz.gantt_duration') }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ $data['project_duration'] }} {{ __('calendar.days') }} ({{ __('projects.workdays_unit') }})</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('viz.gantt_start') }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ \Illuminate\Support\Carbon::parse($data['project_start'])->format('d M Y') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('viz.gantt_finish') }}</p>
            <p class="mt-1 text-xl font-semibold text-indigo-600">{{ \Illuminate\Support\Carbon::parse($data['project_finish'])->format('d M Y') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('viz.gantt_critical_tasks') }}</p>
            <p class="mt-1 text-xl font-semibold text-rose-600">{{ __('viz.gantt_of_tasks', ['critical' => collect($data['tasks'])->where('critical', true)->count(), 'total' => count($data['tasks'])]) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-600">
                <span class="flex items-center gap-1.5"><span class="h-3 w-6 rounded-sm bg-rose-500"></span> ⚠ {{ __('viz.gantt_legend_critical') }}</span>
                <span class="flex items-center gap-1.5"><span class="h-3 w-6 rounded-sm bg-indigo-500"></span> {{ __('viz.gantt_legend_normal') }}</span>
                <span class="flex items-center gap-1.5"><span class="h-3 w-6 rounded-sm bg-slate-800"></span> {{ __('viz.gantt_legend_summary') }}</span>
                <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-3 rotate-45 bg-indigo-600"></span> {{ __('viz.gantt_legend_milestone') }}</span>
                <span class="flex items-center gap-1.5"><span class="h-3 w-6 overflow-hidden rounded-sm border border-slate-300 bg-slate-200"><span class="block h-full w-1/2 bg-emerald-500"></span></span> {{ __('viz.gantt_legend_progress') }}</span>
                <span class="flex items-center gap-1.5"><span class="h-3 w-6 rounded-sm bg-slate-300"></span> {{ __('viz.gantt_legend_nonworking') }}</span>
                <span class="flex items-center gap-1.5">⏰ {{ __('viz.gantt_legend_conflict') }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Filter per anggota (ASN-5). --}}
                @if ($resources->isNotEmpty())
                    <form method="GET" action="{{ route('projects.gantt', $project) }}" class="flex items-center gap-2">
                        <label for="gantt-member-filter" class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('viz.gantt_filter_label') }}</label>
                        <select name="member" id="gantt-member-filter" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('viz.gantt_filter_all') }}</option>
                            @foreach ($resources as $resource)
                                <option value="{{ $resource->id }}" @selected($memberId === (int) $resource->id)>{{ $resource->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <div class="flex items-center gap-1 rounded-lg border border-slate-200 p-1">
                    <span class="px-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('viz.gantt_zoom_label') }}</span>
                    @foreach (['hari' => __('viz.gantt_zoom_day'), 'minggu' => __('viz.gantt_zoom_week'), 'bulan' => __('viz.gantt_zoom_month')] as $value => $label)
                        <button
                            type="button"
                            data-zoom="{{ $value }}"
                            class="rounded-md px-3 py-1 text-xs font-semibold {{ $value === 'minggu' ? 'bg-indigo-600 text-white' : 'text-slate-600' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <div id="gantt-chart"></div>
        <script type="application/json" id="gantt-data">{!! json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    </div>

    <p class="mt-3 text-xs text-slate-500">{{ __('viz.gantt_hint') }}</p>
@endsection
