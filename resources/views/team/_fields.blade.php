@php $editingResource = $editingResource ?? null; @endphp

<div class="grid gap-5">
    <div>
        <label for="member-name" class="mb-1 block text-sm font-medium text-slate-700">{{ __('team.field_name') }} <span class="text-rose-500">*</span></label>
        <input type="text" name="name" id="member-name" value="{{ old('name', $editingResource?->name) }}" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="member-contact" class="mb-1 block text-sm font-medium text-slate-700">{{ __('team.field_contact') }}</label>
            <input type="text" name="contact" id="member-contact" value="{{ old('contact', $editingResource?->contact) }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div>
            <label for="member-role" class="mb-1 block text-sm font-medium text-slate-700">{{ __('team.field_role') }}</label>
            <input type="text" name="role" id="member-role" value="{{ old('role', $editingResource?->role) }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    <div class="max-w-xs">
        <label for="member-salary" class="mb-1 block text-sm font-medium text-slate-700">{{ __('team.field_salary') }}</label>
        <input type="number" name="salary" id="member-salary" min="0" step="1" value="{{ old('salary', $editingResource?->salary) }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="8000000">
        <p class="mt-1 text-xs text-slate-500">{{ __('team.field_salary_hint') }}</p>
    </div>
</div>
