<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Resource;
use App\Models\ResourceLeave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ResourceLeaveController extends Controller
{
    public function store(Request $request, Project $project, Resource $resource): RedirectResponse
    {
        abort_if($resource->project_id !== $project->id, 404);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'start_date.required' => __('team.leave_start_required'),
            'end_date.after_or_equal' => __('team.leave_end_after'),
        ]);

        $resource->leaves()->create($validated);

        return back()->with('success', __('team.leave_recorded', [
            'name' => $resource->name,
            'range' => __('team.leave_range', [
                'from' => Carbon::parse($validated['start_date'])->format('d M Y'),
                'to' => Carbon::parse($validated['end_date'])->format('d M Y'),
            ]),
        ]));
    }

    public function destroy(Project $project, ResourceLeave $leave): RedirectResponse
    {
        $belongsToProject = ResourceLeave::whereKey($leave->id)
            ->whereHas('resource', fn ($query) => $query->where('project_id', $project->id))
            ->exists();

        abort_unless($belongsToProject, 404);

        $leave->delete();

        return back()->with('success', __('team.leave_deleted'));
    }
}
