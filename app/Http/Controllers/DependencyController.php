<?php

namespace App\Http\Controllers;

use App\Models\Dependency;
use App\Models\Project;
use App\Services\Schedule\CircularDependencyException;
use App\Services\ScheduleCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DependencyController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'predecessor_id' => ['required', 'integer', Rule::exists('tasks', 'id')->where('project_id', $project->id)],
            'successor_id' => ['required', 'integer', Rule::exists('tasks', 'id')->where('project_id', $project->id)],
            'type' => ['required', 'string', Rule::in(['FS', 'SS'])],
            // Lag negatif = lead (DEP-4).
            'lag' => ['nullable', 'integer', 'between:-999,999'],
        ], [
            'predecessor_id.exists' => __('tasks.validation_predecessor_exists'),
            'successor_id.exists' => __('tasks.validation_successor_exists'),
            'type.in' => __('tasks.validation_type_in'),
        ]);

        $predecessorId = (int) $validated['predecessor_id'];
        $successorId = (int) $validated['successor_id'];

        if ($predecessorId === $successorId) {
            return back()->with('error', __('tasks.dependency_self'));
        }

        if (Dependency::where('predecessor_id', $predecessorId)->where('successor_id', $successorId)->exists()) {
            return back()->with('error', __('tasks.dependency_exists'));
        }

        try {
            DB::transaction(function () use ($project, $validated): void {
                $dependency = $project->dependencies()->create([
                    'predecessor_id' => (int) $validated['predecessor_id'],
                    'successor_id' => (int) $validated['successor_id'],
                    'type' => $validated['type'],
                    'lag' => (int) ($validated['lag'] ?? 0),
                ]);

                ScheduleCalculator::forProject($project)->calculate($project->fresh('dependencies'));
            });
        } catch (CircularDependencyException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('tasks.dependency_stored'));
    }

    public function destroy(Project $project, Dependency $dependency): RedirectResponse
    {
        abort_if($dependency->project_id !== $project->id, 404);

        $dependency->delete();

        return back()->with('success', __('tasks.dependency_removed'));
    }
}
