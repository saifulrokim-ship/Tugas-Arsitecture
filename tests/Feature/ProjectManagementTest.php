<?php

namespace Tests\Feature;

use App\Models\Dependency;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Resource;
use App\Models\ResourceLeave;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(): Project
    {
        $project = Project::factory()->create(['start_date' => '2026-01-05']);

        $project->calendar()->create([
            'workdays' => [1, 2, 3, 4, 5],
            'weekend_days' => [6, 7],
        ]);

        return $project->fresh();
    }

    public function test_project_can_be_created_with_default_calendar(): void
    {
        $response = $this->post(route('projects.store'), [
            'name' => 'Proyek Uji',
            'organization' => 'Kampus',
            'description' => 'Deskripsi singkat',
            'start_date' => '2026-01-05',
        ]);

        $project = Project::firstWhere('name', 'Proyek Uji');

        $this->assertNotNull($project);
        $response->assertRedirect(route('projects.show', $project));
        $this->assertNotNull($project->calendar);
        $this->assertSame([1, 2, 3, 4, 5], $project->calendar->workdays);
        $this->assertSame([6, 7], $project->calendar->weekend_days);
    }

    public function test_project_name_is_required(): void
    {
        $response = $this->from(route('projects.create'))->post(route('projects.store'), [
            'name' => '',
            'start_date' => '2026-01-05',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Project::count());
    }

    public function test_validation_errors_are_rendered_back_on_the_form(): void
    {
        $this->followingRedirects()
            ->from(route('projects.create'))
            ->post(route('projects.store'), [
                'name' => '',
                'start_date' => '2026-01-05',
            ])
            ->assertOk()
            ->assertSee('The data could not be saved');

        $this->assertSame(0, Project::count());
    }

    public function test_task_can_be_created_with_assignments(): void
    {
        $project = $this->makeProject();
        $resource = Resource::factory()->create(['project_id' => $project->id]);

        $response = $this->post(route('projects.tasks.store', $project), [
            'name' => 'Tugas Baru',
            'duration' => 3,
            'priority' => 'high',
            'start_date' => '2026-01-05',
            'resource_ids' => [$resource->id],
        ]);

        $task = Task::firstWhere('name', 'Tugas Baru');

        $this->assertNotNull($task);
        $this->assertTrue($task->resources->contains($resource));
        $response->assertRedirect(route('projects.tasks.index', $project));
    }

    public function test_subtask_is_grouped_under_its_parent_on_task_index(): void
    {
        $project = $this->makeProject();
        $parent = Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Induk', 'duration' => 1]);
        Task::factory()->create([
            'project_id' => $project->id,
            'parent_id' => $parent->id,
            'name' => 'Sub Kegiatan',
            'duration' => 2,
        ]);

        $this->get(route('projects.tasks.index', $project))
            ->assertOk()
            ->assertSee('Tugas Induk')
            ->assertSee('Sub Kegiatan')
            ->assertSee('Induk');
    }

    public function test_dependency_is_stored_and_rejects_circular_chain(): void
    {
        $project = $this->makeProject();
        $first = Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Pertama', 'start_date' => '2026-01-05', 'duration' => 3]);
        $second = Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Kedua', 'duration' => 2]);

        $this->post(route('projects.dependencies.store', $project), [
            'predecessor_id' => $first->id,
            'successor_id' => $second->id,
            'type' => 'FS',
            'lag' => 1,
        ])->assertSessionHas('success');

        $this->assertSame(1, Dependency::count());

        $this->post(route('projects.dependencies.store', $project), [
            'predecessor_id' => $second->id,
            'successor_id' => $first->id,
            'type' => 'FS',
            'lag' => 0,
        ])->assertSessionHas('error');

        $this->assertSame(1, Dependency::count());
    }

    public function test_calendar_can_be_updated(): void
    {
        $project = $this->makeProject();

        $this->put(route('projects.calendar.update', $project), [
            'workdays' => [1, 2, 3, 4, 5, 6],
            'weekend_days' => [7],
        ])->assertSessionHas('success');

        $calendar = $project->fresh()->calendar;

        $this->assertSame([1, 2, 3, 4, 5, 6], $calendar->workdays);
        $this->assertSame([7], $calendar->weekend_days);
    }

    public function test_calendar_requires_at_least_one_workday(): void
    {
        $project = $this->makeProject();

        $this->put(route('projects.calendar.update', $project), [
            'workdays' => [],
            'weekend_days' => [6, 7],
        ])->assertSessionHasErrors('workdays');
    }

    public function test_holiday_can_be_added_and_removed(): void
    {
        $project = $this->makeProject();

        $this->post(route('projects.holidays.store', $project), [
            'date' => '2026-01-14',
            'description' => 'Hari Libur',
        ])->assertSessionHas('success');

        $holiday = Holiday::first();

        $this->assertNotNull($holiday);
        $this->assertSame('2026-01-14', $holiday->date->format('Y-m-d'));

        $this->delete(route('projects.holidays.destroy', [$project, $holiday]))->assertSessionHas('success');
        $this->assertSame(0, Holiday::count());
    }

    public function test_member_leave_can_be_recorded_and_removed(): void
    {
        $project = $this->makeProject();
        $resource = Resource::factory()->create(['project_id' => $project->id]);

        $this->post(route('projects.leaves.store', [$project, $resource]), [
            'start_date' => '2026-01-12',
            'end_date' => '2026-01-14',
            'description' => 'Cuti tahunan',
        ])->assertSessionHas('success');

        $leave = ResourceLeave::first();

        $this->assertNotNull($leave);

        $this->delete(route('projects.leaves.destroy', [$project, $leave]))->assertSessionHas('success');
        $this->assertSame(0, ResourceLeave::count());
    }

    public function test_salary_is_visible_on_team_page_but_never_in_gantt_or_pert(): void
    {
        $project = $this->makeProject();
        $resource = Resource::factory()->create([
            'project_id' => $project->id,
            'name' => 'Anggota Bergaji',
            'salary' => 12_000_000,
        ]);
        Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Visual', 'start_date' => '2026-01-05', 'duration' => 2]);
        $project->tasks()->first()->resources()->attach($resource->id);

        // FR-21 (PRD diperbarui): gaji tersimpan di data tim dan tampil di halaman tim.
        $this->get(route('projects.team.index', $project))
            ->assertOk()
            ->assertSee('12.000.000');

        // FR-21: gaji tidak pernah ikut ke tampilan Gantt maupun PERT.
        $this->get(route('projects.gantt', $project))
            ->assertOk()
            ->assertDontSee('12.000.000')
            ->assertDontSee('"salary"');

        $this->get(route('projects.pert', $project))
            ->assertOk()
            ->assertDontSee('"salary"');
    }

    public function test_single_user_interface_has_no_role_switcher(): void
    {
        $project = $this->makeProject();
        Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Terlihat', 'duration' => 2]);

        $this->get(route('projects.tasks.index', $project))
            ->assertOk()
            ->assertSee('Tugas Terlihat')
            ->assertSee('Add Task');

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('New Project')
            ->assertDontSee('Peran tampilan')
            ->assertDontSee('Stakeholder (read-only)');
    }

    public function test_locale_switcher_changes_ui_language_and_persists_in_session(): void
    {
        $project = $this->makeProject();
        Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Lokal', 'duration' => 2]);

        // Default English (PRD §10).
        $this->get(route('projects.tasks.index', $project))
            ->assertOk()
            ->assertSee('Add Task')
            ->assertDontSee('+ Tambah Tugas');

        // Ganti ke Indonesia, pilihan disimpan di session.
        $this->post(route('locale.store'), ['locale' => 'id'])
            ->assertRedirect();

        $this->get(route('projects.tasks.index', $project))
            ->assertOk()
            ->assertSee('Tambah Tugas');

        // Nilai locale yang tidak didukung jatuh kembali ke English.
        $this->post(route('locale.store'), ['locale' => 'fr']);

        $this->get(route('projects.tasks.index', $project))
            ->assertOk()
            ->assertSee('Add Task');
    }

    public function test_health_endpoint_reports_ok(): void
    {
        $this->get('/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_gantt_and_pert_pages_render_current_schedule(): void
    {
        $project = $this->makeProject();
        $first = Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Analisis', 'start_date' => '2026-01-05', 'duration' => 3]);
        $second = Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Desain', 'duration' => 2]);

        Dependency::create([
            'project_id' => $project->id,
            'predecessor_id' => $first->id,
            'successor_id' => $second->id,
            'type' => 'FS',
            'lag' => 0,
        ]);

        $this->get(route('projects.gantt', $project))
            ->assertOk()
            ->assertSee('gantt-chart')
            ->assertSee('gantt-data')
            ->assertSee('Tugas Analisis');

        $this->get(route('projects.pert', $project))
            ->assertOk()
            ->assertSee('pert-chart')
            ->assertSee('pert-data')
            ->assertSee('Tugas Desain');
    }

    public function test_visualizations_redirect_with_error_when_dependency_is_circular(): void
    {
        $project = $this->makeProject();
        $first = Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas A', 'duration' => 2]);
        $second = Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas B', 'duration' => 2]);

        Dependency::create(['project_id' => $project->id, 'predecessor_id' => $first->id, 'successor_id' => $second->id, 'type' => 'FS', 'lag' => 0]);
        Dependency::create(['project_id' => $project->id, 'predecessor_id' => $second->id, 'successor_id' => $first->id, 'type' => 'SS', 'lag' => 0]);

        $this->get(route('projects.gantt', $project))
            ->assertRedirect(route('projects.tasks.index', $project))
            ->assertSessionHas('error');

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Circular dependency detected');
    }
}
