@csrf

<div class="grid gap-5">
    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">{{ __('projects.field_name') }} <span class="text-rose-500">*</span></label>
        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $project->name ?? '') }}"
            required
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
    </div>

    <div>
        <label for="organization" class="mb-1 block text-sm font-medium text-slate-700">{{ __('projects.field_organization') }}</label>
        <input
            type="text"
            name="organization"
            id="organization"
            value="{{ old('organization', $project->organization ?? '') }}"
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
    </div>

    <div>
        <label for="description" class="mb-1 block text-sm font-medium text-slate-700">{{ __('projects.field_description') }}</label>
        <textarea
            name="description"
            id="description"
            rows="3"
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >{{ old('description', $project->description ?? '') }}</textarea>
    </div>

    <div class="max-w-xs">
        <label for="start_date" class="mb-1 block text-sm font-medium text-slate-700">{{ __('projects.field_start_date') }} <span class="text-rose-500">*</span></label>
        <input
            type="date"
            name="start_date"
            id="start_date"
            value="{{ old('start_date', isset($project) && $project->start_date ? $project->start_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
            required
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
    </div>
</div>
