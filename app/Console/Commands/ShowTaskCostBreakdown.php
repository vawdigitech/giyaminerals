<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class ShowTaskCostBreakdown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:show-cost-breakdown {task_id : The ID of the task to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show detailed cost breakdown for a task and its subtasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $taskId = $this->argument('task_id');
        $task = Task::with(['subtasks', 'assignments.employee', 'parent'])->find($taskId);

        if (!$task) {
            $this->error("Task #{$taskId} not found.");
            return 1;
        }

        $this->info("=" . str_repeat("=", 78));
        $this->info("TASK COST BREAKDOWN");
        $this->info("=" . str_repeat("=", 78));
        $this->newLine();

        // Show task details
        $this->line("Task ID: {$task->id}");
        $this->line("Code: {$task->code}");
        $this->line("Name: {$task->name}");
        $this->line("Type: " . ($task->hasSubtasks() ? 'Master Task' : 'Leaf Task'));
        if ($task->parent_id) {
            $this->line("Parent: #{$task->parent_id} - {$task->parent->code}");
        }
        $this->newLine();

        // Show direct costs (for leaf tasks)
        if (!$task->hasSubtasks()) {
            $this->info("DIRECT COSTS (Leaf Task):");
            $this->line("  Labor Cost: " . number_format($task->labor_cost ?? 0, 2));
            $this->line("  Material Cost: " . number_format($task->material_cost ?? 0, 2));
            $this->line("  Actual Amount: " . number_format($task->actual_amount ?? 0, 2));
            $this->line("  Total Hours: " . number_format($task->total_hours_worked ?? 0, 2));
            $this->newLine();

            // Show assignments
            if ($task->assignments->count() > 0) {
                $this->info("EMPLOYEE ASSIGNMENTS:");
                $this->table(
                    ['Employee', 'Location', 'Hours', 'Rate', 'Cost', 'Status'],
                    $task->assignments->map(function ($assignment) {
                        return [
                            $assignment->employee->name,
                            $assignment->location_type . ':' . $assignment->location_id,
                            number_format($assignment->hours_worked, 2),
                            number_format($assignment->hourly_rate_at_time, 2),
                            number_format($assignment->hours_worked * $assignment->hourly_rate_at_time, 2),
                            $assignment->removed_at ? 'Removed' : 'Active',
                        ];
                    })->toArray()
                );
                $this->newLine();

                // Calculate expected labor cost
                $expectedLaborCost = $task->assignments
                    ->whereNull('removed_at')
                    ->sum(function ($a) {
                        return $a->hours_worked * $a->hourly_rate_at_time;
                    });

                $this->line("Expected Labor Cost (from assignments): " . number_format($expectedLaborCost, 2));
                $this->line("Actual Labor Cost (in database): " . number_format($task->labor_cost ?? 0, 2));

                if (abs($expectedLaborCost - ($task->labor_cost ?? 0)) > 0.01) {
                    $this->error("⚠ MISMATCH DETECTED!");
                } else {
                    $this->info("✓ Labor cost is correct");
                }
                $this->newLine();
            }
        }

        // Show aggregated costs
        $this->info("AGGREGATED COSTS:");
        $this->line("  Aggregated Labor Cost: " . number_format($task->aggregated_labor_cost ?? 0, 2));
        $this->line("  Aggregated Material Cost: " . number_format($task->aggregated_material_cost ?? 0, 2));
        $this->line("  Aggregated Actual Amount: " . number_format($task->aggregated_actual_amount ?? 0, 2));
        $this->line("  Aggregated Quoted Amount: " . number_format($task->aggregated_quoted_amount ?? 0, 2));
        $this->newLine();

        // Show subtasks if any
        if ($task->hasSubtasks()) {
            $this->info("SUBTASKS ({$task->subtasks->count()}):");
            $this->table(
                ['ID', 'Code', 'Name', 'Type', 'Labor', 'Material', 'Total', 'Hours'],
                $task->subtasks->map(function ($subtask) {
                    return [
                        $subtask->id,
                        $subtask->code,
                        strlen($subtask->name) > 30 ? substr($subtask->name, 0, 27) . '...' : $subtask->name,
                        $subtask->hasSubtasks() ? 'Master' : 'Leaf',
                        number_format($subtask->aggregated_labor_cost ?? 0, 2),
                        number_format($subtask->aggregated_material_cost ?? 0, 2),
                        number_format($subtask->aggregated_actual_amount ?? 0, 2),
                        number_format($subtask->total_hours_worked ?? 0, 2),
                    ];
                })->toArray()
            );
            $this->newLine();

            // Calculate expected aggregated costs
            $expectedAggLaborCost = $task->subtasks->sum('aggregated_labor_cost') ?? 0;
            $expectedAggMaterialCost = $task->subtasks->sum('aggregated_material_cost') ?? 0;
            $expectedAggTotalCost = $expectedAggLaborCost + $expectedAggMaterialCost;

            $this->line("Expected Aggregated Labor Cost (from subtasks): " . number_format($expectedAggLaborCost, 2));
            $this->line("Actual Aggregated Labor Cost (in database): " . number_format($task->aggregated_labor_cost ?? 0, 2));

            if (abs($expectedAggLaborCost - ($task->aggregated_labor_cost ?? 0)) > 0.01) {
                $this->error("⚠ MISMATCH DETECTED!");
                $this->line("Run: php artisan tasks:recalculate-aggregated");
            } else {
                $this->info("✓ Aggregated costs are correct");
            }
            $this->newLine();
        }

        // Show hierarchy path
        $ancestors = $task->getAncestors();
        if ($ancestors->count() > 0) {
            $this->info("HIERARCHY PATH:");
            foreach ($ancestors->reverse() as $index => $ancestor) {
                $indent = str_repeat("  ", $index);
                $this->line("{$indent}├─ #{$ancestor->id} {$ancestor->code} (Agg: " . number_format($ancestor->aggregated_labor_cost ?? 0, 2) . ")");
            }
            $indent = str_repeat("  ", $ancestors->count());
            $this->line("{$indent}└─ #{$task->id} {$task->code} (Current)");
            $this->newLine();
        }

        $this->info("=" . str_repeat("=", 78));

        return 0;
    }
}
