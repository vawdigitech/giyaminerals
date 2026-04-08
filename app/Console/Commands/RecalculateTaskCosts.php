<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class RecalculateTaskCosts extends Command
{
    protected $signature = 'tasks:recalculate-costs {--task_id=}';
    protected $description = 'Recalculate costs and aggregated costs for all tasks';

    public function handle()
    {
        $taskId = $this->option('task_id');

        if ($taskId) {
            $task = Task::find($taskId);
            if (!$task) {
                $this->error("Task with ID {$taskId} not found.");
                return 1;
            }

            $this->info("Recalculating costs for task: {$task->code} - {$task->name}");
            $task->recalculateCosts();
            $this->info("Done!");

            $task->refresh();
            $this->info("Labor Cost: {$task->labor_cost}");
            $this->info("Material Cost: {$task->material_cost}");
            $this->info("Actual Amount: {$task->actual_amount}");
            $this->info("Aggregated Labor Cost: {$task->aggregated_labor_cost}");
            $this->info("Aggregated Material Cost: {$task->aggregated_material_cost}");
            $this->info("Aggregated Actual Amount: {$task->aggregated_actual_amount}");

            if ($task->parent_id) {
                $parent = $task->parent;
                $this->info("\nParent Task: {$parent->code} - {$parent->name}");
                $this->info("Aggregated Labor Cost: {$parent->aggregated_labor_cost}");
                $this->info("Aggregated Material Cost: {$parent->aggregated_material_cost}");
                $this->info("Aggregated Actual Amount: {$parent->aggregated_actual_amount}");
            }

            return 0;
        }

        $this->info('Recalculating costs for all tasks...');

        // First recalculate all leaf tasks (tasks without subtasks)
        $leafTasks = Task::whereDoesntHave('subtasks')->get();
        $bar = $this->output->createProgressBar($leafTasks->count());
        $bar->start();

        foreach ($leafTasks as $task) {
            $task->recalculateCosts();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Then recalculate all parent tasks
        $this->info('Recalculating aggregated costs for parent tasks...');
        $parentTasks = Task::has('subtasks')->get();
        $bar = $this->output->createProgressBar($parentTasks->count());
        $bar->start();

        foreach ($parentTasks as $task) {
            $task->recalculateAggregatedCosts();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All tasks recalculated successfully!');

        return 0;
    }
}
