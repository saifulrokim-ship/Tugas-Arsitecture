@extends('layouts.app')

@section('title', __('calendar.index_title'))

@section('subtitle', __('calendar.index_subtitle'))

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('projects.calendar.update', $project) }}" class="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            @method('PUT')

            <h2 class="text-sm font-semibold text-slate-900">{{ __('calendar.week_heading') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('calendar.week_intro') }}</p>

            <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('calendar.workdays_label') }}</p>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach (\Illuminate\Support\Arr::wrap(__('calendar.day_names')) as $index => $dayName)
                    @php
                        $dayNumber = $index + 1;
                        $workdays = old('workdays', $project->calendar?->workdays ?? \App\Services\WorkCalendar::DEFAULT_WORKDAYS);
                    @endphp
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <input
                            type="checkbox"
                            name="workdays[]"
                            value="{{ $dayNumber }}"
                            @checked(in_array($dayNumber, $workdays))
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        {{ $dayName }}
                    </label>
                @endforeach
            </div>

            <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('calendar.weekend_label') }}</p>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach (\Illuminate\Support\Arr::wrap(__('calendar.day_names')) as $index => $dayName)
                    @php
                        $dayNumber = $index + 1;
                        $weekendDays = old('weekend_days', $project->calendar?->weekend_days ?? \App\Services\WorkCalendar::DEFAULT_WEEKEND_DAYS);
                    @endphp
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <input
                            type="checkbox"
                            name="weekend_days[]"
                            value="{{ $dayNumber }}"
                            @checked(in_array($dayNumber, $weekendDays))
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        {{ $dayName }}
                    </label>
                @endforeach
            </div>

            <div class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">{{ __('calendar.save_calendar') }}</button>
                <span class="text-xs text-slate-500">{{ __('calendar.save_note') }}</span>
            </div>
        </form>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('calendar.holidays_heading') }}</h2>

                {{-- Import template hari libur nasional (CAL-4): satu klik, tanpa duplikat. --}}
                <form method="POST" action="{{ route('projects.holidays.import', $project) }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                        {{ __('calendar.template_import') }}
                    </button>
                </form>
            </div>

            <form method="POST" action="{{ route('projects.holidays.store', $project) }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-lg bg-slate-50 p-3">
                @csrf
                <div>
                    <label for="holiday-date" class="mb-1 block text-xs font-medium text-slate-600">{{ __('calendar.holiday_date') }}</label>
                    <input type="date" name="date" id="holiday-date" value="{{ old('date') }}" required class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="holiday-end-date" class="mb-1 block text-xs font-medium text-slate-600">{{ __('calendar.holiday_end_date') }}</label>
                    <input type="date" name="end_date" id="holiday-end-date" value="{{ old('end_date') }}" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="min-w-40 flex-1">
                    <label for="holiday-description" class="mb-1 block text-xs font-medium text-slate-600">{{ __('calendar.holiday_description') }}</label>
                    <input type="text" name="description" id="holiday-description" value="{{ old('description') }}" placeholder="{{ __('calendar.holiday_description_placeholder') }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('calendar.holiday_add') }}</button>
            </form>
            <p class="mt-2 text-xs text-slate-500">{{ __('calendar.holiday_end_hint') }} · {{ __('calendar.template_import_note') }}</p>

            <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-2">{{ __('calendar.holiday_col_date') }}</th>
                            <th scope="col" class="px-4 py-2">{{ __('calendar.holiday_col_description') }}</th>
                            <th scope="col" class="px-4 py-2 text-right">{{ __('tasks.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($project->holidays as $holiday)
                            <tr>
                                <td class="px-4 py-2 text-slate-700">
                                    @if ($holiday->end_date && ! $holiday->end_date->eq($holiday->date))
                                        {{ __('calendar.holiday_range', ['from' => $holiday->date->format('d M Y'), 'to' => $holiday->end_date->format('d M Y')]) }}
                                    @else
                                        {{ $holiday->date->format('d M Y') }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-slate-700">{{ $holiday->description ?: '—' }}</td>
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ route('projects.holidays.destroy', [$project, $holiday]) }}" onsubmit="return confirm('{{ e(__('calendar.holiday_delete_confirm')) }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-rose-200 px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">{{ __('common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">{{ __('calendar.holiday_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-xs text-slate-500">{{ __('calendar.holiday_note') }}</p>
        </div>
    </div>
@endsection
