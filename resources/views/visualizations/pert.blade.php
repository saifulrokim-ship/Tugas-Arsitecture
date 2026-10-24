@extends('layouts.app')

@section('title', __('viz.pert_title'))

@section('subtitle', __('viz.pert_subtitle'))

@section('actions')
    <a href="{{ route('projects.gantt', $project) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('viz.pert_view_gantt') }}</a>
    <a href="{{ route('projects.tasks.index', $project) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('viz.pert_view_tasks') }}</a>
@endsection

@section('content')
    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('viz.pert_duration') }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ $data['project_duration'] }} {{ __('projects.workdays_unit') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('viz.pert_finish') }}</p>
            <p class="mt-1 text-xl font-semibold text-indigo-600">{{ \Illuminate\Support\Carbon::parse($data['project_finish'])->format('d M Y') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('viz.pert_visible_nodes') }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ collect($data['tasks'])->where('is_summary', false)->count() }} {{ __('viz.pert_tasks_unit') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('viz.pert_critical_nodes') }}</p>
            <p class="mt-1 text-xl font-semibold text-rose-600">{{ collect($data['tasks'])->where('critical', true)->where('is_summary', false)->count() }} {{ __('viz.pert_tasks_unit') }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600">
                <span class="flex items-center gap-1.5"><span class="h-3 w-6 rounded-sm border-2 border-rose-500 bg-rose-50"></span> ⚠ {{ __('viz.pert_legend_critical') }}</span>
                <span class="flex items-center gap-1.5"><span class="h-3 w-6 rounded-sm border-2 border-slate-200 bg-white"></span> {{ __('viz.pert_legend_normal') }}</span>
                <span class="flex items-center gap-1.5">{{ __('viz.pert_legend_values') }}</span>
            </div>

            {{-- Pan, zoom in/out/reset (PRT-3). --}}
            <div class="flex items-center gap-1 rounded-lg border border-slate-200 p-1">
                <button type="button" data-pert-zoom="out" aria-label="{{ __('viz.pert_zoom_out') }}" title="{{ __('viz.pert_zoom_out') }}" class="rounded-md px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">−</button>
                <button type="button" data-pert-zoom="reset" class="rounded-md px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">{{ __('viz.pert_zoom_reset') }}</button>
                <button type="button" data-pert-zoom="in" aria-label="{{ __('viz.pert_zoom_in') }}" title="{{ __('viz.pert_zoom_in') }}" class="rounded-md px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">+</button>
            </div>
        </div>

        <div id="pert-chart"></div>
        <div id="pert-detail" class="hidden border-t border-slate-100 px-5 py-4"></div>
        <script type="application/json" id="pert-data">{!! json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    </div>

    <p class="mt-3 text-xs text-slate-500">{{ __('viz.pert_summary_note') }} {{ __('viz.pert_critical_note') }}</p>
    <p class="mt-1 text-xs text-slate-500">{{ __('viz.pert_hint') }}</p>
@endsection
