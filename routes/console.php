<?php

use App\Models\Dependency;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('benchmark:seed-500', function () {
    $start = microtime(true);

    // Reset state - just delete all rows
    DB::statement('DELETE FROM dependencies');
    DB::statement('DELETE FROM holidays');
    DB::statement('DELETE FROM resources');
    DB::statement('DELETE FROM tasks');
    DB::statement('DELETE FROM projects');

    // Create 100 projects with tasks (smaller scale for SQLite)
    for ($i = 1; $i <= 100; $i++) {
        $projectName = 'Project '.$i;
        $project = Project::create([
            'name' => $projectName,
            'organization' => 'Organization '.$i,
            'description' => 'Description for project '.$i,
            'start_date' => now()->startOfWeek()->addDays($i * 7)->toDateString(),
        ]);

        $project->calendar()->create([
            'workdays' => [1, 2, 3, 4, 5],
            'weekend_days' => [6, 7],
        ]);

        // Add 3 holidays per project
        for ($h = 0; $h < 3; $h++) {
            Holiday::create([
                'project_id' => $project->id,
                'date' => now()->addDays($i * 7 + $h)->toDateString(),
                'description' => 'Holiday '.$h,
            ]);
        }

        // Add 5 resources per project
        for ($r = 0; $r < 5; $r++) {
            App\Models\Resource::create([
                'name' => "Resource {$r}",
                'contact' => "resource{$r}@example.com",
                'role' => "Role {$r}",
                'project_id' => $project->id,
            ]);
        }

        // Add 10 tasks per project with dependencies
        $tasks = [];
        $prevTask = null;
        for ($t = 0; $t < 10; $t++) {
            $task = Task::create([
                'name' => "Task {$t} - {$projectName}",
                'project_id' => $project->id,
                'start_date' => null,
                'duration' => 1,
                'priority' => 'medium',
                'sort_order' => $t,
            ]);

            $tasks[$t] = $task;

            if ($prevTask) {
                Dependency::create([
                    'project_id' => $project->id,
                    'predecessor_id' => $prevTask->id,
                    'successor_id' => $task->id,
                    'type' => 'FS',
                    'lag' => 0,
                ]);
            }
            $prevTask = $task;
        }

        // Assign 2 resources per task
        $resources = $project->resources->all();
        foreach ($tasks as $idx => $taskItem) {
            $taskItem->resources()->attach($resources[$idx % 5]->id);
            $taskItem->resources()->attach($resources[($idx + 1) % 5]->id);
        }
    }

    $elapsed = microtime(true) - $start;
    $msg = 'Seeded 100 projects with tasks, dependencies, and resources in '.number_format($elapsed, 2).'s';
    $this->info($msg);
})->purpose('Seed projects with tasks for benchmarking');
