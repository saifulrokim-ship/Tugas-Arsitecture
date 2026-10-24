@extends('layouts.app')

@section('title', __('team.index_title'))

@section('subtitle', __('team.index_subtitle'))

@section('actions')
        <a href="{{ route('projects.team.create', $project) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            {{ __('team.add_member') }}
        </a>
    {{-- Toggle salary (RES-5): preferensi session. --}}
    <form method="POST" action="{{ route('projects.team.salary-visibility', $project) }}">
        @csrf
        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            {{ $salaryVisible ? __('team.hide_salary') : __('team.show_salary') }}
        </button>
    </form>
@endsection

@section('content')
    @if ($resources->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-12 text-center shadow-sm">
            <p class="text-sm text-slate-500">{{ __('team.empty') }}</p>
                <a href="{{ route('projects.team.create', $project) }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('team.add_first_member') }}</a>
        </div>
    @else
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach ($resources as $resource)
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $resource->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $resource->role ?: __('team.no_role') }} · {{ $resource->contact ?: __('team.no_contact') }}</p>
                            <p class="mt-2 text-xs text-slate-600">
                                {{ __('team.assigned_count') }}
                                <span class="font-semibold text-slate-800">{{ $resource->assigned_task_count }}</span>
                                {{ __('team.assigned_count_unit') }}
                                    · {{ __('team.salary_label') }}
                                    @if ($salaryVisible)
                                        <span class="font-semibold text-slate-800">Rp {{ number_format((int) $resource->salary, 0, ',', '.') }}</span>{{ __('team.salary_per_month') }}
                                    @else
                                        <span class="font-semibold text-slate-800">{{ __('team.salary_hidden') }}</span>
                                    @endif
                            </p>
                            {{-- Peringatan bila member masih punya assignment (RES-2). --}}
                            @if ($resource->assigned_task_count > 0)
                                <p class="mt-2 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs text-amber-700">⚠ {{ __('team.warning_assigned', ['count' => $resource->assigned_task_count]) }}</p>
                            @endif
                        </div>
                            <div class="flex shrink-0 gap-1.5">
                                <a href="{{ route('projects.team.edit', [$project, $resource]) }}" class="rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('common.edit') }}</a>
                                <form method="POST" action="{{ route('projects.team.destroy', [$project, $resource]) }}" onsubmit="return confirm('{{ e(__('team.delete_confirm', ['name' => $resource->name])) }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md border border-rose-200 px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">{{ __('common.delete') }}</button>
                                </form>
                            </div>
                    </div>

                    <div class="px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('team.leave_heading') }}</p>

                        <ul class="mt-2 space-y-1.5">
                            @forelse ($resource->leaves->sortBy('start_date') as $leave)
                                <li class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                    <span class="min-w-0 text-slate-700">
                                        <span class="font-medium">{{ $leave->start_date->format('d M Y') }}</span>
                                        – {{ $leave->end_date->format('d M Y') }}
                                        <span class="text-xs text-slate-500">{{ $leave->description ? __('team.leave_note_prefix', ['note' => $leave->description]) : '' }}</span>
                                    </span>
                                        <form method="POST" action="{{ route('projects.leaves.destroy', [$project, $leave]) }}" onsubmit="return confirm('{{ e(__('team.leave_delete_confirm')) }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="shrink-0 rounded-md border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">{{ __('common.delete') }}</button>
                                        </form>
                                </li>
                            @empty
                                <li class="text-sm text-slate-500">{{ __('team.empty_leave') }}</li>
                            @endforelse
                        </ul>

                            <form method="POST" action="{{ route('projects.leaves.store', [$project, $resource]) }}" class="mt-3 flex flex-wrap items-end gap-2 rounded-lg border border-dashed border-slate-300 p-3">
                                @csrf
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600" for="leave-start-{{ $resource->id }}">{{ __('team.leave_start') }}</label>
                                    <input type="date" name="start_date" id="leave-start-{{ $resource->id }}" required class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600" for="leave-end-{{ $resource->id }}">{{ __('team.leave_end') }}</label>
                                    <input type="date" name="end_date" id="leave-end-{{ $resource->id }}" required class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="min-w-32 flex-1">
                                    <label class="mb-1 block text-xs font-medium text-slate-600" for="leave-desc-{{ $resource->id }}">{{ __('team.leave_description') }}</label>
                                    <input type="text" name="description" id="leave-desc-{{ $resource->id }}" placeholder="{{ __('team.leave_description_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('team.leave_record') }}</button>
                            </form>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-xs text-slate-500">{{ __('team.salary_encrypted_note') }}</p>
    @endif
@endsection
