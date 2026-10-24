<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectFileService;
use App\Services\Schedule\CircularDependencyException;
use App\Services\ScheduleCalculator;
use App\Services\WorkCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->withCount(['tasks', 'resources', 'holidays'])
            ->orderBy('name')
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
        ], [
            'name.required' => __('projects.validation_name_required'),
            'start_date.required' => __('projects.validation_start_required'),
        ]);

        $project = Project::create($validated);
        $project->calendar()->create([
            'workdays' => WorkCalendar::DEFAULT_WORKDAYS,
            'weekend_days' => WorkCalendar::DEFAULT_WEEKEND_DAYS,
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', __('projects.created', ['name' => $project->name]));
    }

    public function show(Project $project): View
    {
        $project->load(['calendar', 'holidays' => fn ($query) => $query->orderBy('date')]);

        $schedule = null;
        $scheduleError = null;

        try {
            $schedule = ScheduleCalculator::forProject($project)->calculate($project);
        } catch (CircularDependencyException $exception) {
            $scheduleError = $exception->getMessage();
        }

        $project->load(['tasks' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'), 'tasks.resources', 'resources.leaves']);

        return view('projects.show', compact('project', 'schedule', 'scheduleError'));
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
        ], [
            'name.required' => __('projects.validation_name_required'),
            'start_date.required' => __('projects.validation_start_required'),
        ]);

        $project->update($validated);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', __('projects.updated'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        $name = $project->name;
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', __('projects.deleted', ['name' => $name]));
    }

    /**
     * Simpan seluruh isi proyek ke file lokal (single-user, gaya GanttProject).
     */
    public function export(Project $project, ProjectFileService $files): StreamedResponse
    {
        $filename = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $project->name)).'.proyekta.json';

        return response()->streamDownload(function () use ($project, $files): void {
            echo $files->toJson($project);
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function createImport(): View
    {
        return view('projects.import');
    }

    /**
     * Buka proyek dari file lokal hasil ekspor.
     */
    public function import(Request $request, ProjectFileService $files): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ], [
            'file.required' => __('projects.import_validation_file_required'),
            'file.file' => __('projects.import_validation_file_file'),
            'file.max' => __('projects.import_validation_file_max'),
        ]);

        try {
            $payload = json_decode($request->file('file')->get(), true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($payload)) {
                throw new InvalidArgumentException('Isi file bukan data proyek yang sah.');
            }

            $project = $files->import($payload);
        } catch (\JsonException $exception) {
            return back()->with('error', __('projects.import_invalid_json'))->withInput();
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (\Throwable $exception) {
            Log::error('Gagal membuka file proyek', ['error' => $exception->getMessage()]);

            return back()->with('error', __('projects.import_failed'))->withInput();
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', __('projects.imported', ['name' => $project->name]));
    }
}
