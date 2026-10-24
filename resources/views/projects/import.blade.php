@extends('layouts.app')

@section('title', __('projects.import_title'))

@section('subtitle', __('projects.import_subtitle'))

@section('content')
    <div class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-600">
            {{ __('projects.import_intro') }}
        </p>

        <form method="POST" action="{{ route('projects.import') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf

            <div>
                <label for="project-file" class="mb-1 block text-sm font-medium text-slate-700">{{ __('projects.import_file_label') }} <span class="text-rose-500">*</span></label>
                <input
                    type="file"
                    name="file"
                    id="project-file"
                    accept=".json,application/json"
                    required
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('file')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ __('projects.import_submit') }}</button>
                <a href="{{ route('projects.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">{{ __('common.back') }}</a>
            </div>
        </form>
    </div>
@endsection
