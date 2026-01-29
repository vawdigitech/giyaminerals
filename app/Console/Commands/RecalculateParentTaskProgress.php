<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class RecalculateParentTaskProgress extends Command
{
    protected $signature = 'tasks:recalculate-progress';

    protected $description = 'Recalculate progress for all parent tasks based on their subtasks';

    public function handle()
    {
        $this->info('Recalculating parent task progress...');

        // Get all parent tasks (tasks that have subtasks)
        $parentTasks = Task::whereNull('parent_id')
            ->whereHas('subtasks')
            ->get();

        $count = 0;

        foreach ($parentTasks as $task) {
            $oldProgress = $task->progress;
            $task->recalculateProgressFromSubtasks();
            $task->refresh();

            if ($oldProgress != $task->progress) {
                $this->line("Task {$task->code} ({$task->name}): {$oldProgress}% -> {$task->progress}%");
                $count++;
            }
        }

        $this->info("Done! Updated {$count} parent tasks.");

        return Command::SUCCESS;
    }
}
