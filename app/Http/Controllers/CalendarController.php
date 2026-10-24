<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function edit(Project $project): View
    {
        $project->load(['calendar', 'holidays' => fn ($query) => $query->orderBy('date')]);

        return view('calendar.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'workdays' => ['required', 'array', 'min:1'],
            'workdays.*' => ['integer', 'between:1,7'],
            'weekend_days' => ['array'],
            'weekend_days.*' => ['integer', 'between:1,7'],
        ], [
            'workdays.required' => __('calendar.workdays_required'),
        ]);

        $workdays = array_values(array_map('intval', $validated['workdays']));
        $weekendDays = array_values(array_map('intval', $validated['weekend_days'] ?? []));

        $calendar = $project->calendar ?: $project->calendar()->make();

        $calendar->fill([
            'project_id' => $project->id,
            'workdays' => $workdays,
            'weekend_days' => $weekendDays,
        ])->save();

        return back()->with('success', __('calendar.updated', [
            'workdays' => $this->dayNames($workdays),
            'weekend' => $weekendDays === [] ? '-' : $this->dayNames($weekendDays),
        ]));
    }

    /**
     * @param  list<int>  $days
     */
    private function dayNames(array $days): string
    {
        $names = __('calendar.day_names');

        return implode(', ', array_map(
            static fn (int $day): string => is_array($names) ? ($names[$day - 1] ?? (string) $day) : (string) $day,
            $days,
        ));
    }
}
