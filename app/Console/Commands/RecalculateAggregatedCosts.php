<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class RecalculateAggregatedCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:recalculate-aggregated {--project= : Only recalculate for specific project ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate aggregated costs for all master tasks from their subtasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = $this->option('project');

        if ($projectId) {
            $this->info("Recalculating aggregated costs for project ID: {$projectId}");
        } else {
            $this->info('Recalculating aggregated costs for all tasks...');
        }

        // Step 1: First update all leaf tasks (tasks without subtasks)
        $this->info('Step 1: Updating leaf task aggregated costs...');

        $leafQuery = Task::whereDoesntHave('subtasks');
        if ($projectId) {
            $leafQuery->where('project_id', $projectId);
        }
        $leafTasks = $leafQuery->get();

        $this->info("Found {$leafTasks->count()} leaf tasks");
        $leafBar = $this->output->createProgressBar($leafTasks->count());
        $leafBar->start();

        foreach ($leafTasks as $task) {
            // For leaf tasks, aggregated = direct
            $task->aggregated_labor_cost = $task->labor_cost ?? 0;
            $task->aggregated_material_cost = $task->material_cost ?? 0;
            $task->aggregated_actual_amount = $task->actual_amount ?? 0;
            $task->aggregated_quoted_amount = $task->quoted_amount ?? 0;
            $task->saveQuietly();
            $leafBar->advance();
        }

        $leafBar->finish();
        $this->newLine();

        // Step 2: Get all master tasks (tasks with subtasks) ordered by depth (deepest first)
        $this->info('Step 2: Updating master task aggregated costs...');

        $masterQuery = Task::has('subtasks')->with('subtasks');
        if ($projectId) {
            $masterQuery->where('project_id', $projectId);
        }
        $masterTasks = $masterQuery->get();

        // Sort by nesting level (deepest first) so we calculate from bottom to top
        $masterTasks = $masterTasks->sortByDesc(function ($task) {
            return $task->getNestingLevel();
        });

        $this->info("Found {$masterTasks->count()} master tasks");
        $masterBar = $this->output->createProgressBar($masterTasks->count());
        $masterBar->start();

        foreach ($masterTasks as $task) {
            // Get fresh subtask data
            $subtasks = $task->subtasks()->get();

            // Sum aggregated values from subtasks
            $task->aggregated_labor_cost = $subtasks->sum('aggregated_labor_cost') ?? 0;
            $task->aggregated_material_cost = $subtasks->sum('aggregated_material_cost') ?? 0;
            $task->aggregated_actual_amount = $task->aggregated_labor_cost + $task->aggregated_material_cost;
            $task->aggregated_quoted_amount = $subtasks->sum('aggregated_quoted_amount') ?? 0;
            $task->total_hours_worked = $subtasks->sum('total_hours_worked') ?? 0;
            $task->saveQuietly();

            $masterBar->advance();
        }

        $masterBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Recalculation complete!');
        $this->table(
            ['Type', 'Count'],
            [
                ['Leaf tasks updated', $leafTasks->count()],
                ['Master tasks updated', $masterTasks->count()],
                ['Total tasks processed', $leafTasks->count() + $masterTasks->count()],
            ]
        );

        // Show some examples
        $this->newLine();
        $this->info('Sample of updated master tasks:');

        $sampleQuery = Task::has('subtasks')->with('subtasks');
        if ($projectId) {
            $sampleQuery->where('project_id', $projectId);
        }
        $samples = $sampleQuery->take(5)->get();

        foreach ($samples as $task) {
            $this->line("Task: {$task->code} - {$task->name}");
            $this->line("  Subtasks: {$task->subtasks->count()}");
            $this->line("  Aggregated Labor Cost: " . number_format($task->aggregated_labor_cost, 2));
            $this->line("  Aggregated Material Cost: " . number_format($task->aggregated_material_cost, 2));
            $this->line("  Aggregated Total: " . number_format($task->aggregated_actual_amount, 2));
            $this->line("  Total Hours: " . number_format($task->total_hours_worked, 2));
            $this->newLine();
        }

        return 0;
    }
}
