<?php

namespace Tests\Feature;

use App\Models\Dependency;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Task;
use App\Services\ProjectFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProjectFileTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(): Project
    {
        $project = Project::factory()->create([
            'name' => 'Proyek Lokal',
            'organization' => 'Kampus',
            'description' => 'Simpan ke file',
            'start_date' => '2026-01-05',
        ]);

        $project->calendar()->create([
            'workdays' => [1, 2, 3, 4, 5],
            'weekend_days' => [6, 7],
        ]);

        $project->holidays()->create(['date' => '2026-01-01', 'description' => 'Tahun Baru']);

        $parent = Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Induk', 'start_date' => '2026-01-05', 'duration' => 5]);
        $child = Task::factory()->create(['project_id' => $project->id, 'parent_id' => $parent->id, 'name' => 'Sub Kegiatan', 'duration' => 2]);
        $next = Task::factory()->create(['project_id' => $project->id, 'name' => 'Tugas Lanjutan', 'duration' => 3]);

        Dependency::create([
            'project_id' => $project->id,
            'predecessor_id' => $child->id,
            'successor_id' => $next->id,
            'type' => 'FS',
            'lag' => 1,
        ]);

        $resource = Resource::factory()->create([
            'project_id' => $project->id,
            'name' => 'Anggota Simpan',
            'salary' => 9_000_000,
        ]);

        $resource->leaves()->create([
            'start_date' => '2026-01-12',
            'end_date' => '2026-01-13',
            'description' => 'Cuti',
        ]);

        $next->resources()->attach($resource->id);

        return $project->fresh();
    }

    public function test_import_form_route_is_reachable(): void
    {
        $this->get(route('projects.import.create'))
            ->assertOk()
            ->assertSee('Open Project from File');
    }

    public function test_project_can_be_exported_as_json_file(): void
    {
        $project = $this->makeProject();

        $response = $this->get(route('projects.export', $project));

        $response->assertOk();
        $this->assertStringContainsString('.proyekta.json', (string) $response->headers->get('content-disposition'));

        $payload = json_decode($response->streamedContent(), true);

        $this->assertSame('proyekta-project', $payload['format']);
        $this->assertSame('Proyek Lokal', $payload['project']['name']);
        $this->assertCount(1, $payload['holidays']);
        $this->assertCount(3, $payload['tasks']);
        $this->assertCount(1, $payload['dependencies']);
        $this->assertSame(1, $payload['dependencies'][0]['lag']);
        $this->assertCount(1, $payload['resources']);
        $this->assertSame(9_000_000, $payload['resources'][0]['salary']);
        $this->assertCount(1, $payload['resources'][0]['leaves']);
        $this->assertCount(1, $payload['assignments']);
        $this->assertSame([1, 2, 3, 4, 5], $payload['calendar']['workdays']);
    }

    public function test_project_can_be_reopened_from_exported_file(): void
    {
        $source = $this->makeProject();
        $json = app(ProjectFileService::class)->toJson($source);

        $response = $this->post(route('projects.import'), [
            'file' => UploadedFile::fake()->createWithContent('proyek-lokal.proyekta.json', $json),
        ]);

        $project = Project::where('name', 'Proyek Lokal')->orderBy('id', 'desc')->first();

        $this->assertNotNull($project);
        $this->assertNotSame($source->id, $project->id);
        $response->assertRedirect(route('projects.show', $project));

        $this->assertSame(1, $project->holidays()->count());
        $this->assertSame(3, $project->tasks()->count());
        $this->assertSame(1, $project->dependencies()->count());
        $this->assertSame(1, $project->resources()->count());
        $this->assertSame(1, $project->resources()->first()->leaves()->count());
        $this->assertSame(9_000_000, $project->resources()->first()->salary);

        // Struktur hierarki dan alokasi tetap utuh.
        $parent = $project->tasks()->whereNull('parent_id')->first();
        $this->assertSame('Tugas Induk', $parent->name);
        $this->assertSame(1, $parent->children()->count());

        $assigned = $project->tasks()->where('name', 'Tugas Lanjutan')->first();
        $this->assertTrue($assigned->resources->contains('name', 'Anggota Simpan'));

        // Jadwal tetap dapat dihitung setelah dibuka dari file (sinkron Gantt/PERT).
        $this->get(route('projects.gantt', $project))->assertOk()->assertSee('gantt-chart');
    }

    public function test_import_rejects_invalid_file(): void
    {
        $before = Project::count();

        $this->from(route('projects.import.create'))
            ->post(route('projects.import'), [
                'file' => UploadedFile::fake()->createWithContent('rusak.json', 'ini bukan json {'),
            ])
            ->assertRedirect(route('projects.import.create'))
            ->assertSessionHas('error');

        $this->assertSame($before, Project::count());

        $this->from(route('projects.import.create'))
            ->post(route('projects.import'), [
                'file' => UploadedFile::fake()->createWithContent('salah-format.json', json_encode(['format' => 'lain', 'project' => ['name' => 'X']])),
            ])
            ->assertSessionHas('error');

        $this->assertSame($before, Project::count());
    }

    public function test_import_requires_a_file(): void
    {
        $this->from(route('projects.import.create'))
            ->post(route('projects.import'), [])
            ->assertSessionHasErrors('file');
    }
}
