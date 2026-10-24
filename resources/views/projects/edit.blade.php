@extends('layouts.app')

@section('title', __('projects.edit_title'))

@section('subtitle', $project->name)

@section('content')
    <div class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('projects.update', $project) }}">
            @method('PUT')
            @include('projects._form', ['project' => $project])

            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    {{ __('common.save_changes') }}
                </button>
                <a href="{{ route('projects.show', $project) }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
