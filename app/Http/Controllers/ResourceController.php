<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Resource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(Project $project): View
    {
        $resources = $project->resources()
            ->with('leaves')
            ->withCount(['tasks as assigned_task_count'])
            ->orderBy('name')
            ->get();

        // Preferensi view salary (RES-5): disimpan di session; default tampil.
        $salaryVisible = ! (bool) request()->session()->get('salary_hidden', false);

        return view('team.index', compact('project', 'resources', 'salaryVisible'));
    }

    public function create(Project $project): View
    {
        return view('team.create', compact('project'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $resource = $project->resources()->create($this->validateResource($request));

        return redirect()
            ->route('projects.team.index', $project)
            ->with('success', __('team.created', ['name' => $resource->name]));
    }

    public function edit(Project $project, Resource $team): View
    {
        $this->ensureBelongsTo($project, $team);

        return view('team.edit', ['project' => $project, 'resource' => $team]);
    }

    public function update(Request $request, Project $project, Resource $team): RedirectResponse
    {
        $this->ensureBelongsTo($project, $team);

        $team->update($this->validateResource($request));

        return redirect()
            ->route('projects.team.index', $project)
            ->with('success', __('team.updated', ['name' => $team->name]));
    }

    public function destroy(Project $project, Resource $team): RedirectResponse
    {
        $this->ensureBelongsTo($project, $team);

        $name = $team->name;
        $team->delete();

        return redirect()
            ->route('projects.team.index', $project)
            ->with('success', __('team.deleted', ['name' => $name]));
    }

    /**
     * Toggle tampil/sembunyi salary di halaman tim (RES-5): preferensi session.
     */
    public function toggleSalaryVisibility(Request $request, Project $project): RedirectResponse
    {
        $hidden = ! (bool) $request->session()->get('salary_hidden', false);

        $request->session()->put('salary_hidden', $hidden);

        return back()->with('success', __($hidden ? 'team.salary_hidden_flash' : 'team.salary_shown'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateResource(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
        ], [
            'name.required' => __('team.validation_name_required'),
            'salary.integer' => __('team.validation_salary_integer'),
        ]);
    }

    private function ensureBelongsTo(Project $project, Resource $resource): void
    {
        abort_if($resource->project_id !== $project->id, 404);
    }
}
