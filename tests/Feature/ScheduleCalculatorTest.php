<?php

namespace Tests\Feature;

use App\Models\Dependency;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Task;
use App\Services\Schedule\CircularDependencyException;
use App\Services\Schedule\ScheduleResult;
use App\Services\ScheduleCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(array $attributes = []): Project
    {
        $project = Project::factory()->create(array_merge([
            'start_date' => '2026-01-05',
        ], $attributes));

        $project->calendar()->create([
            'workdays' => [1, 2, 3, 4, 5],
            'weekend_days' => [6, 7],
        ]);

        return $project->fresh();
    }

    private function makeTask(Project $project, array $attributes = []): Task
    {
        return Task::factory()->create(array_merge([
            'project_id' => $project->id,
            'sort_order' => 1,
        ], $attributes));
    }

    private function link(Project $project, Task $predecessor, Task $successor, string $type = 'FS', int $lag = 0): Dependency
    {
        return Dependency::create([
            'project_id' => $project->id,
            'predecessor_id' => $predecessor->id,
            'successor_id' => $successor->id,
            'type' => $type,
            'lag' => $lag,
        ]);
    }

    private function calculate(Project $project): ScheduleResult
    {
        $project = $project->fresh();

        return ScheduleCalculator::forProject($project)->calculate($project);
    }

    public function test_finish_to_start_chain_skips_weekend(): void
    {
        $project = $this->makeProject();
        $first = $this->makeTask($project, ['name' => 'Analisis', 'start_date' => '2026-01-05', 'duration' => 3, 'priority' => 'high']);
        $second = $this->makeTask($project, ['name' => 'Desain', 'duration' => 2, 'sort_order' => 2]);
        $this->link($project, $first, $second);

        $result = $this->calculate($project);

        $this->assertSame('2026-01-05', $result->task($first->id)->start->format('Y-m-d'));
        $this->assertSame('2026-01-07', $result->task($first->id)->finish->format('Y-m-d'));
        $this->assertSame('2026-01-08', $result->task($second->id)->start->format('Y-m-d'));
        $this->assertSame('2026-01-09', $result->task($second->id)->finish->format('Y-m-d'));
        $this->assertSame('2026-01-09', $result->projectFinish->format('Y-m-d'));
        $this->assertSame(5, $result->projectDuration);
        $this->assertTrue($result->task($first->id)->critical);
        $this->assertTrue($result->task($second->id)->critical);
    }

    public function test_start_to_start_dependency_starts_together_with_lag(): void
    {
        $project = $this->makeProject();
        $first = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 4]);
        $second = $this->makeTask($project, ['duration' => 2, 'sort_order' => 2]);
        $this->link($project, $first, $second, 'SS', 2);

        $result = $this->calculate($project);

        $this->assertSame('2026-01-07', $result->task($second->id)->start->format('Y-m-d'));
    }

    public function test_lag_delays_successor_by_working_days(): void
    {
        $project = $this->makeProject();
        $first = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 3]);
        $second = $this->makeTask($project, ['duration' => 1, 'sort_order' => 2]);
        $this->link($project, $first, $second, 'FS', 1);

        $result = $this->calculate($project);

        $this->assertSame('2026-01-09', $result->task($second->id)->start->format('Y-m-d'));
    }

    public function test_successor_takes_the_latest_of_multiple_predecessors(): void
    {
        $project = $this->makeProject();
        $early = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 1]);
        $late = $this->makeTask($project, ['start_date' => '2026-01-07', 'duration' => 1, 'sort_order' => 2]);
        $successor = $this->makeTask($project, ['duration' => 1, 'sort_order' => 3]);
        $this->link($project, $early, $successor);
        $this->link($project, $late, $successor);

        $result = $this->calculate($project);

        $this->assertSame('2026-01-08', $result->task($successor->id)->start->format('Y-m-d'));
    }

    public function test_holidays_are_skipped_in_duration(): void
    {
        $project = $this->makeProject();
        Holiday::create(['project_id' => $project->id, 'date' => '2026-01-08', 'description' => 'Libur Nasional']);
        $first = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 3]);
        $second = $this->makeTask($project, ['duration' => 2, 'sort_order' => 2]);
        $this->link($project, $first, $second);

        $result = $this->calculate($project->fresh());

        $this->assertSame('2026-01-09', $result->task($second->id)->start->format('Y-m-d'));
        $this->assertSame('2026-01-12', $result->task($second->id)->finish->format('Y-m-d'));
    }

    public function test_manual_start_is_pushed_to_dependency_constraint(): void
    {
        $project = $this->makeProject();
        $first = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 3]);
        $second = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 1, 'sort_order' => 2]);
        $this->link($project, $first, $second);

        $result = $this->calculate($project);

        $this->assertSame('2026-01-08', $result->task($second->id)->start->format('Y-m-d'));
    }

    public function test_manual_start_later_than_constraint_is_respected(): void
    {
        $project = $this->makeProject();
        $first = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 1]);
        $second = $this->makeTask($project, ['start_date' => '2026-01-15', 'duration' => 1, 'sort_order' => 2]);
        $this->link($project, $first, $second);

        $result = $this->calculate($project);

        $this->assertSame('2026-01-15', $result->task($second->id)->start->format('Y-m-d'));
    }

    public function test_summary_task_takes_dates_from_children(): void
    {
        $project = $this->makeProject();
        $parent = $this->makeTask($project, ['name' => 'Fase 1', 'duration' => 1, 'sort_order' => 1]);
        $first = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 3, 'parent_id' => $parent->id]);
        $second = $this->makeTask($project, ['duration' => 2, 'parent_id' => $parent->id, 'sort_order' => 2]);
        $this->link($project, $first, $second);

        $result = $this->calculate($project);

        $summary = $result->task($parent->id);
        $this->assertTrue($summary->isSummary);
        $this->assertSame('2026-01-05', $summary->start->format('Y-m-d'));
        $this->assertSame('2026-01-09', $summary->finish->format('Y-m-d'));
        $this->assertSame(5, $summary->duration);
        $this->assertSame([$parent->id, $first->id, $second->id], $result->orderedTaskIds());
    }

    public function test_tasks_with_float_are_not_critical(): void
    {
        $project = $this->makeProject();
        $first = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 3]);
        $second = $this->makeTask($project, ['duration' => 2, 'sort_order' => 2]);
        $floaty = $this->makeTask($project, ['name' => 'Tugas longgar', 'start_date' => '2026-01-05', 'duration' => 1, 'sort_order' => 3]);
        $this->link($project, $first, $second);

        $result = $this->calculate($project);

        $this->assertSame(0, $result->task($first->id)->slack);
        $this->assertSame(4, $result->task($floaty->id)->slack);
        $this->assertFalse($result->task($floaty->id)->critical);
        $this->assertContains($floaty->id, $result->orderedTaskIds());
        $this->assertNotContains($floaty->id, $result->criticalTaskIds());
    }

    public function test_circular_dependency_is_rejected_with_clear_message(): void
    {
        $project = $this->makeProject();
        $first = $this->makeTask($project, ['name' => 'Tugas A', 'duration' => 1]);
        $second = $this->makeTask($project, ['name' => 'Tugas B', 'duration' => 1, 'sort_order' => 2]);
        $this->link($project, $first, $second);
        $this->link($project, $second, $first);

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage('Circular dependency detected on:');

        $this->calculate($project);
    }

    public function test_dependency_on_itself_is_rejected(): void
    {
        $project = $this->makeProject();
        $task = $this->makeTask($project, ['name' => 'Tugas Ajaib', 'duration' => 1]);

        Dependency::create([
            'project_id' => $project->id,
            'predecessor_id' => $task->id,
            'successor_id' => $task->id,
            'type' => 'FS',
            'lag' => 0,
        ]);

        $this->expectException(CircularDependencyException::class);

        $this->calculate($project);
    }

    public function test_critical_path_recomputes_when_schedule_changes(): void
    {
        $project = $this->makeProject();
        $first = $this->makeTask($project, ['start_date' => '2026-01-05', 'duration' => 3]);
        $second = $this->makeTask($project, ['duration' => 2, 'sort_order' => 2]);
        $this->link($project, $first, $second);

        $before = $this->calculate($project);
        $this->assertTrue($before->task($second->id)->critical);

        $second->update(['duration' => 4]);

        $after = $this->calculate($project);
        $this->assertSame('2026-01-13', $after->task($second->id)->finish->format('Y-m-d'));
        $this->assertSame('2026-01-13', $after->projectFinish->format('Y-m-d'));
        $this->assertTrue($after->task($second->id)->critical);
    }

    public function test_schedule_of_500_tasks_is_calculated_under_two_seconds(): void
    {
        $project = $this->makeProject();
        $previous = null;

        for ($index = 1; $index <= 500; $index++) {
            $task = $this->makeTask($project, [
                'name' => "Tugas {$index}",
                'start_date' => $previous === null ? '2026-01-05' : null,
                'duration' => 1,
                'sort_order' => $index,
            ]);

            if ($previous !== null) {
                $this->link($project, $previous, $task);
            }

            $previous = $task;
        }

        $started = microtime(true);
        $result = $this->calculate($project);
        $elapsed = microtime(true) - $started;

        $this->assertCount(500, $result->tasks);
        $this->assertLessThan(2.0, $elapsed);
    }
}
