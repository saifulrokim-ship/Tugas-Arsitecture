@php
    $editingTask = $editingTask ?? null;
    $selectedParent = old('parent_id', $editingTask?->parent_id ?? $parentId ?? '');
    $selectedResources = old('resource_ids', $assignedIds ?? []);
    $parentIds = $tasks->pluck('parent_id')->filter()->all();
    $parentOptions = [];
    $collectOptions = function ($parentId, int $level) use (&$collectOptions, &$parentOptions, $tasks, $parentIds, $editingTask): void {
        foreach ($tasks->filter(fn ($candidate) => $candidate->parent_id === $parentId) as $candidate) {
            if ($editingTask === null || $candidate->id !== $editingTask->id) {
                $parentOptions[] = [
                    'id' => $candidate->id,
                    'label' => str_repeat('└ ', $level).$candidate->name.(in_array($candidate->id, $parentIds, true) ? ' '.__('tasks.parent_option_suffix') : ''),
                ];
                $collectOptions($candidate->id, $level + 1);
            }
        }
    };
    $collectOptions(null, 0);
@endphp

<div class="grid gap-5">
    <div>
        <label for="task-name" class="mb-1 block text-sm font-medium text-slate-700">{{ __('tasks.field_name') }} <span class="text-rose-500">*</span></label>
        <input
            type="text"
            name="name"
            id="task-name"
            value="{{ old('name', $editingTask?->name) }}"
            required
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="{{ __('tasks.field_name_placeholder') }}"
        >
    </div>

    <div>
        <label for="task-description" class="mb-1 block text-sm font-medium text-slate-700">{{ __('tasks.field_description') }}</label>
        <textarea
            name="description"
            id="task-description"
            rows="3"
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >{{ old('description', $editingTask?->description) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="task-parent" class="mb-1 block text-sm font-medium text-slate-700">{{ __('tasks.field_parent') }}</label>
            <select name="parent_id" id="task-parent" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('tasks.parent_none') }}</option>
                @foreach ($parentOptions as $option)
                    <option value="{{ $option['id'] }}" @selected((string) $selectedParent === (string) $option['id'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="task-start" class="mb-1 block text-sm font-medium text-slate-700">{{ __('tasks.field_start') }}</label>
            <input
                type="date"
                name="start_date"
                id="task-start"
                value="{{ old('start_date', $editingTask?->start_date?->format('Y-m-d')) }}"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
            <p class="mt-1 text-xs text-slate-500">{{ __('tasks.field_start_hint') }}</p>
        </div>

        <div>
            <label for="task-duration" class="mb-1 block text-sm font-medium text-slate-700">{{ __('tasks.field_duration') }} <span class="text-rose-500">*</span></label>
            <input
                type="number"
                name="duration"
                id="task-duration"
                min="0"
                max="999"
                value="{{ old('duration', $editingTask?->duration ?? 1) }}"
                required
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
            <p class="mt-1 text-xs text-slate-500">{{ __('tasks.field_duration_hint') }}</p>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="max-w-xs">
            <label for="task-percent" class="mb-1 block text-sm font-medium text-slate-700">{{ __('tasks.field_percent') }}</label>
            <input
                type="number"
                name="percent_complete"
                id="task-percent"
                min="0"
                max="100"
                step="1"
                value="{{ old('percent_complete', $editingTask?->percent_complete ?? 0) }}"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>
    </div>

    <div>
        <span class="mb-1 block text-sm font-medium text-slate-700">{{ __('tasks.field_priority') }}</span>
        <div class="flex flex-wrap gap-3">
            @foreach (['low' => __('tasks.priority_low'), 'medium' => __('tasks.priority_medium'), 'high' => __('tasks.priority_high'), 'critical' => __('tasks.priority_critical')] as $value => $label)
                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    <input
                        type="radio"
                        name="priority"
                        value="{{ $value }}"
                        @checked(old('priority', $editingTask?->priority ?? 'medium') === $value)
                        class="border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <div>
        <span class="mb-1 block text-sm font-medium text-slate-700">{{ __('tasks.field_assignees') }}</span>
        @if ($resources->isEmpty())
            <p class="text-sm text-slate-500">{!! __('tasks.assignees_empty', ['link' => '<a href="'.route('projects.team.index', $project).'" class="font-semibold text-indigo-600 underline">'.__('tasks.assignees_link').'</a>']) !!}</p>
        @else
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($resources as $resource)
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <input
                            type="checkbox"
                            name="resource_ids[]"
                            value="{{ $resource->id }}"
                            @checked(in_array($resource->id, $selectedResources))
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-slate-800">{{ $resource->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $resource->role ?: __('team.no_role') }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
        {{ __('tasks.duration_note') }}
    </p>
</div>
