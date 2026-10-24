<?php

namespace Database\Seeders;

use App\Models\Dependency;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Task;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with one complete demo project so the
     * Gantt, PERT and critical path views are immediately populated.
     */
    public function run(): void
    {
        if (Project::query()->exists()) {
            return;
        }

        $weekStart = now()->startOfWeek();

        $project = Project::create([
            'name' => 'Redesign Portal Akademik',
            'organization' => 'Fakultas Teknik',
            'description' => 'Perancangan ulang portal akademik: analisis kebutuhan, desain antarmuka, implementasi, hingga pengujian dan rilis.',
            'start_date' => $weekStart->toDateString(),
        ]);

        $project->calendar()->create([
            'workdays' => [1, 2, 3, 4, 5],
            'weekend_days' => [6, 7],
        ]);

        Holiday::create([
            'project_id' => $project->id,
            'date' => $weekStart->copy()->addDays(19)->toDateString(),
            'description' => 'Hari Libur Nasional',
        ]);

        $team = collect([
            ['name' => 'Andi Pratama', 'contact' => 'andi@example.com', 'role' => 'UI/UX Designer', 'salary' => 9_500_000],
            ['name' => 'Budi Santoso', 'contact' => '0812-3456-7890', 'role' => 'Backend Developer', 'salary' => 12_000_000],
            ['name' => 'Citra Lestari', 'contact' => 'citra@example.com', 'role' => 'Frontend Developer', 'salary' => 11_000_000],
            ['name' => 'Dewi Anggraini', 'contact' => '0813-9999-1111', 'role' => 'QA Engineer', 'salary' => 8_750_000],
        ])->map(fn (array $member) => Resource::create($member + ['project_id' => $project->id]));

        $task = function (array $attributes) use ($project): Task {
            static $order = 0;
            $order++;

            return Task::create($attributes + [
                'project_id' => $project->id,
                'start_date' => null,
                'duration' => 1,
                'priority' => 'medium',
                'sort_order' => $order,
            ]);
        };

        $link = function (Task $predecessor, Task $successor, string $type = 'FS', int $lag = 0) use ($project): void {
            Dependency::create([
                'project_id' => $project->id,
                'predecessor_id' => $predecessor->id,
                'successor_id' => $successor->id,
                'type' => $type,
                'lag' => $lag,
            ]);
        };

        $phase1 = $task(['name' => 'Fase 1 — Analisis', 'duration' => 1, 'priority' => 'high']);
        $needs = $task(['name' => 'Pengumpulan kebutuhan', 'duration' => 3, 'priority' => 'high', 'parent_id' => $phase1->id]);
        $interview = $task(['name' => 'Wawancara stakeholder', 'duration' => 2, 'parent_id' => $phase1->id]);
        $analysis = $task(['name' => 'Analisis & dokumen requirement', 'duration' => 3, 'parent_id' => $phase1->id]);

        $phase2 = $task(['name' => 'Fase 2 — Desain', 'duration' => 1]);
        $wireframe = $task(['name' => 'Wireframe halaman utama', 'duration' => 3, 'parent_id' => $phase2->id]);
        $uiDesign = $task(['name' => 'Desain UI katalog & detail', 'duration' => 4, 'priority' => 'high', 'parent_id' => $phase2->id]);

        $phase3 = $task(['name' => 'Fase 3 — Implementasi', 'duration' => 1, 'priority' => 'high']);
        $backend = $task(['name' => 'Backend API (auth & data)', 'duration' => 5, 'parent_id' => $phase3->id]);
        $frontend = $task(['name' => 'Frontend antarmuka', 'duration' => 5, 'parent_id' => $phase3->id]);
        $integration = $task(['name' => 'Integrasi & migrasi data', 'duration' => 3, 'priority' => 'high', 'parent_id' => $phase3->id]);

        $testing = $task(['name' => 'Pengujian fungsional', 'duration' => 3, 'priority' => 'high']);
        $deployment = $task(['name' => 'Deployment & serah terima', 'duration' => 1, 'priority' => 'high']);

        $link($needs, $interview);
        $link($interview, $analysis);
        $link($analysis, $wireframe);
        $link($wireframe, $uiDesign, 'SS', 1);
        $link($wireframe, $backend);
        $link($uiDesign, $frontend);
        $link($backend, $integration);
        $link($frontend, $integration);
        $link($integration, $testing);
        $link($testing, $deployment);

        $needs->resources()->attach($team[0]->id);
        $interview->resources()->attach([$team[0]->id, $team[3]->id]);
        $analysis->resources()->attach($team[0]->id);
        $wireframe->resources()->attach($team[0]->id);
        $uiDesign->resources()->attach($team[0]->id);
        $backend->resources()->attach($team[1]->id);
        $frontend->resources()->attach($team[2]->id);
        $integration->resources()->attach([$team[1]->id, $team[2]->id]);
        $testing->resources()->attach($team[3]->id);
        $deployment->resources()->attach($team[1]->id);

        // Cuti satu anggota yang bertabrak dengan tugas untuk memunculkan peringatan FR-23.
        $team[3]->leaves()->create([
            'start_date' => $weekStart->copy()->addDays(3)->toDateString(),
            'end_date' => $weekStart->copy()->addDays(5)->toDateString(),
            'description' => 'Cuti keluarga',
        ]);
    }
}
