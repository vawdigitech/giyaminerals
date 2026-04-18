<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class AddTasksToAllProjectLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:add-to-all-locations {--project= : Specific project ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all existing tasks to all their project locations (site + all factories)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Adding tasks to all project locations...');

        $projectId = $this->option('project');

        $query = Project::with(['sites', 'factories', 'tasks']);

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->error('No projects found.');
            return 1;
        }

        $totalAdded = 0;
        $totalSkipped = 0;

        foreach ($projects as $project) {
            $this->line("\nProcessing Project: {$project->name} (ID: {$project->id})");

            // Get all locations for this project
            $locations = [];

            foreach ($project->sites as $site) {
                $locations[] = [
                    'type' => 'site',
                    'id' => $site->id,
                    'name' => $site->name,
                ];
            }

            foreach ($project->factories as $factory) {
                $locations[] = [
                    'type' => 'factory',
                    'id' => $factory->id,
                    'name' => $factory->name,
                ];
            }

            if (empty($locations)) {
                $this->warn("  ⚠ Project has no locations assigned. Skipping.");
                continue;
            }

            $this->info("  Locations: " . count($locations));

            // Process each task
            foreach ($project->tasks as $task) {
                $this->line("  Task: {$task->code} - {$task->name}");

                $addedCount = 0;

                foreach ($locations as $location) {
                    // Check if task is already at this location
                    $exists = DB::table('task_locations')
                        ->where('task_id', $task->id)
                        ->where('location_type', $location['type'])
                        ->where('location_id', $location['id'])
                        ->exists();

                    if ($exists) {
                        $this->line("    ✓ Already at {$location['name']} ({$location['type']})");
                        $totalSkipped++;
                        continue;
                    }

                    // Add task to this location
                    DB::table('task_locations')->insert([
                        'task_id' => $task->id,
                        'location_type' => $location['type'],
                        'location_id' => $location['id'],
                        'progress' => 0,
                        'status' => 'pending',
                        'labor_cost' => 0,
                        'material_cost' => 0,
                        'total_cost' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->info("    ✅ Added to {$location['name']} ({$location['type']})");
                    $addedCount++;
                    $totalAdded++;
                }

                if ($addedCount > 0) {
                    $this->line("    Added to {$addedCount} new location(s)");
                }
            }
        }

        $this->newLine();
        $this->info("✅ Complete!");
        $this->info("   Added: {$totalAdded}");
        $this->info("   Skipped (already exists): {$totalSkipped}");

        return 0;
    }
}
