@extends('layouts.app')

@section('title', __('tasks.edit_title'))

@section('subtitle', $task->name)

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}">
                @csrf
                @method('PUT')
                @include('tasks._fields', ['editingTask' => $task, 'parentId' => null])

                <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ __('common.save_changes') }}</button>
                    <a href="{{ route('projects.tasks.index', $project) }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">{{ __('common.back') }}</a>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('tasks.dependencies_heading') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('tasks.dependencies_intro') }}</p>

                <div class="mt-4 space-y-2">
                    @forelse ($dependencies as $dependency)
                        <div class="flex items-start justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2">
                            <div class="min-w-0 text-sm text-slate-700">
                                <p class="truncate font-medium">{{ $dependency->predecessor?->name ?: __('tasks.dependency_deleted_task') }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $dependency->type === 'SS' ? __('tasks.dependency_ss_label') : __('tasks.dependency_fs_label') }}
                                    @if ($dependency->lag !== 0)
                                        · {{ __('tasks.dependency_lag_days', ['count' => $dependency->lag]) }}
                                    @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('projects.dependencies.destroy', [$project, $dependency]) }}" onsubmit="return confirm('{{ e(__('tasks.dependency_delete_confirm')) }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="shrink-0 rounded-md border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">{{ __('common.delete') }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('tasks.dependency_empty') }}</p>
                    @endforelse
                </div>

                    <form method="POST" action="{{ route('projects.dependencies.store', $project) }}" class="mt-4 space-y-3 rounded-lg bg-slate-50 p-3">
                        @csrf
                        <input type="hidden" name="successor_id" value="{{ $task->id }}">

                        <div>
                            <label for="dep-predecessor" class="mb-1 block text-xs font-medium text-slate-600">{{ __('tasks.dependency_predecessor_label') }}</label>
                            <select name="predecessor_id" id="dep-predecessor" required class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('tasks.dependency_pick') }}</option>
                                @foreach ($candidates as $candidate)
                                    <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex gap-3">
                            <div class="flex-1">
                                <label for="dep-type" class="mb-1 block text-xs font-medium text-slate-600">{{ __('tasks.dependency_type_label') }}</label>
                                <select name="type" id="dep-type" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="FS">FS — {{ __('tasks.dependency_fs_label') }}</option>
                                    <option value="SS">SS — {{ __('tasks.dependency_ss_label') }}</option>
                                </select>
                            </div>
                            <div class="w-24">
                                <label for="dep-lag" class="mb-1 block text-xs font-medium text-slate-600">{{ __('tasks.dependency_lag_label') }}</label>
                                <input type="number" name="lag" id="dep-lag" min="-999" max="999" value="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('tasks.dependency_add') }}</button>
                    </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('tasks.schedule_heading') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{!! __('tasks.schedule_intro', ['link' => '<a href="'.route('projects.tasks.index', $project).'" class="font-semibold text-indigo-600 underline">'.__('tasks.schedule_link').'</a>']) !!}</p>
            </div>
        </div>
    </div>
@endsection
