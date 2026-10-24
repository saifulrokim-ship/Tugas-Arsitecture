@extends('layouts.app')

@section('title', __('projects.index_title'))

@section('actions')
    <a href="{{ route('projects.import.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100">
        {{ __('projects.open_from_file') }}
    </a>
    <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
        {{ __('projects.new_project') }}
    </a>
@endsection

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('projects.index_heading') }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('projects.index_intro') }}</p>
        </div>

        @if ($projects->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-slate-500">{{ __('projects.empty') }}</p>
                    <a href="{{ route('projects.create') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        {{ __('projects.empty_cta') }}
                    </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">{{ __('projects.col_name') }}</th>
                            <th scope="col" class="px-5 py-3">{{ __('projects.col_organization') }}</th>
                            <th scope="col" class="px-5 py-3 text-center">{{ __('projects.col_tasks') }}</th>
                            <th scope="col" class="px-5 py-3 text-center">{{ __('projects.col_team') }}</th>
                            <th scope="col" class="px-5 py-3 text-center">{{ __('projects.col_holidays') }}</th>
                            <th scope="col" class="px-5 py-3 text-center">{{ __('projects.col_start') }}</th>
                            <th scope="col" class="px-5 py-3 text-right">{{ __('tasks.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($projects as $project)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('projects.show', $project) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $project->name }}</a>
                                    @if ($project->description)
                                        <p class="mt-0.5 max-w-md truncate text-xs text-slate-500">{{ $project->description }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $project->organization ?: '—' }}</td>
                                <td class="px-5 py-3 text-center text-slate-600">{{ $project->tasks_count }}</td>
                                <td class="px-5 py-3 text-center text-slate-600">{{ $project->resources_count }}</td>
                                <td class="px-5 py-3 text-center text-slate-600">{{ $project->holidays_count }}</td>
                                <td class="px-5 py-3 text-center text-slate-600">{{ $project->start_date->format('d M Y') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('projects.show', $project) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('common.open') }}</a>
                                            <a href="{{ route('projects.edit', $project) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('common.edit') }}</a>
                                            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('{{ e(__('projects.delete_confirm', ['name' => $project->name])) }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">{{ __('common.delete') }}</button>
                                            </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
