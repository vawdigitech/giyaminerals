<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create task_locations pivot table
        // Tracks which locations a task is assigned to, with per-location progress/costs
        Schema::create('task_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('location_type'); // 'site' or 'factory'
            $table->unsignedBigInteger('location_id'); // site_id or factory_id
            $table->integer('progress')->default(0); // 0-100
            $table->string('status')->default('pending'); // pending, in_progress, completed, on_hold
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('material_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->timestamps();

            // Ensure a task can't be added to the same location twice
            $table->unique(['task_id', 'location_type', 'location_id']);
        });

        // Add location tracking to task_assignments
        // This tells us which location (site/factory) an assignment is for
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->string('location_type')->nullable()->after('task_id'); // 'site' or 'factory'
            $table->unsignedBigInteger('location_id')->nullable()->after('location_type'); // site_id or factory_id
        });

        // Add location tracking to task_stock_usages
        Schema::table('task_stock_usages', function (Blueprint $table) {
            $table->string('location_type')->nullable()->after('task_id'); // 'site' or 'factory'
            $table->unsignedBigInteger('location_id')->nullable()->after('location_type'); // site_id or factory_id
        });

        // Migrate existing tasks to task_locations
        // For each task, create a task_location entry based on the task's site_id or factory_id
        DB::table('tasks')->get()->each(function ($task) {
            if ($task->site_id) {
                DB::table('task_locations')->insert([
                    'task_id' => $task->id,
                    'location_type' => 'site',
                    'location_id' => $task->site_id,
                    'progress' => $task->progress ?? 0,
                    'status' => $task->status ?? 'pending',
                    'labor_cost' => $task->labor_cost ?? 0,
                    'material_cost' => $task->material_cost ?? 0,
                    'total_cost' => $task->actual_amount ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update existing assignments for this task
                DB::table('task_assignments')
                    ->where('task_id', $task->id)
                    ->update([
                        'location_type' => 'site',
                        'location_id' => $task->site_id,
                    ]);

                // Update existing stock usages for this task
                DB::table('task_stock_usages')
                    ->where('task_id', $task->id)
                    ->update([
                        'location_type' => 'site',
                        'location_id' => $task->site_id,
                    ]);
            } elseif ($task->factory_id) {
                DB::table('task_locations')->insert([
                    'task_id' => $task->id,
                    'location_type' => 'factory',
                    'location_id' => $task->factory_id,
                    'progress' => $task->progress ?? 0,
                    'status' => $task->status ?? 'pending',
                    'labor_cost' => $task->labor_cost ?? 0,
                    'material_cost' => $task->material_cost ?? 0,
                    'total_cost' => $task->actual_amount ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update existing assignments for this task
                DB::table('task_assignments')
                    ->where('task_id', $task->id)
                    ->update([
                        'location_type' => 'factory',
                        'location_id' => $task->factory_id,
                    ]);

                // Update existing stock usages for this task
                DB::table('task_stock_usages')
                    ->where('task_id', $task->id)
                    ->update([
                        'location_type' => 'factory',
                        'location_id' => $task->factory_id,
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove location tracking from task_stock_usages
        Schema::table('task_stock_usages', function (Blueprint $table) {
            $table->dropColumn(['location_type', 'location_id']);
        });

        // Remove location tracking from task_assignments
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->dropColumn(['location_type', 'location_id']);
        });

        // Drop task_locations table
        Schema::dropIfExists('task_locations');
    }
};
