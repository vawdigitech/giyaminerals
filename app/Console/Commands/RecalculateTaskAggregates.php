<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;

class RecalculateTaskAggregates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:recalculate-aggregates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate aggregated costs and quoted amounts for all tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recalculating aggregated values for all tasks...');

        // Get all tasks ordered from leaf to root (to ensure subtasks are processed first)
        $tasks = Task::with('subtasks')
            ->orderBy('parent_id', 'desc')
            ->get();

        $processed = 0;

        foreach ($tasks as $task) {
            $task->recalculateAggregatedCosts();
            $processed++;

            if ($processed % 50 === 0) {
                $this->info("Processed {$processed} tasks...");
            }
        }

        $this->info("Completed! Recalculated aggregates for {$processed} tasks.");

        return Command::SUCCESS;
    }
}
