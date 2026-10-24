<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Project;
use App\Services\HolidayTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validateHoliday($request);

        $holiday = $project->holidays()->create($validated);

        $label = $holiday->end_date && ! $holiday->end_date->eq($holiday->date)
            ? __('calendar.holiday_range', [
                'from' => $holiday->date->format('d M Y'),
                'to' => $holiday->end_date->format('d M Y'),
            ])
            : $holiday->date->format('d M Y');

        return back()->with('success', __('calendar.holiday_created', ['date' => $label]));
    }

    /**
     * Import template hari libur nasional Indonesia — satu klik, aman diulang (CAL-4).
     */
    public function importTemplate(Project $project, HolidayTemplateService $template): RedirectResponse
    {
        $result = $template->import($project);

        if ($result['created'] === 0) {
            return back()->with(
                'success',
                __('calendar.template_none_new', ['year' => $result['year']])
            );
        }

        return back()->with('success', __('calendar.template_imported', [
            'count' => $result['created'],
            'year' => $result['year'],
            'skipped' => $result['skipped'],
        ]));
    }

    public function update(Request $request, Project $project, Holiday $holiday): RedirectResponse
    {
        $this->ensureBelongsTo($project, $holiday);

        $holiday->update($this->validateHoliday($request));

        return back()->with('success', __('calendar.holiday_updated'));
    }

    public function destroy(Project $project, Holiday $holiday): RedirectResponse
    {
        $this->ensureBelongsTo($project, $holiday);

        $holiday->delete();

        return back()->with('success', __('calendar.holiday_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateHoliday(Request $request): array
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'date.required' => __('calendar.holiday_date_required'),
            'end_date.after_or_equal' => __('calendar.holiday_end_after'),
        ]);

        if (empty($validated['end_date']) || $validated['end_date'] === $validated['date']) {
            unset($validated['end_date']);
            $validated['end_date'] = null;
        }

        return $validated;
    }

    private function ensureBelongsTo(Project $project, Holiday $holiday): void
    {
        abort_if($holiday->project_id !== $project->id, 404);
    }
}
