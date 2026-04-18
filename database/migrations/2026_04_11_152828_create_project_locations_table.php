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
        // Create pivot table for many-to-many relationship between projects and locations
        Schema::create('project_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->cascadeOnDelete();
            $table->foreignId('factory_id')->nullable()->constrained('factories')->cascadeOnDelete();
            $table->timestamps();

            // Ensure either site_id or factory_id is filled (not both, not neither)
            // This will be enforced at the application level
            $table->unique(['project_id', 'site_id', 'factory_id']);
        });

        // Migrate existing project location data to project_locations table
        DB::table('projects')->whereNotNull('site_id')->orWhereNotNull('factory_id')->get()->each(function ($project) {
            DB::table('project_locations')->insert([
                'project_id' => $project->id,
                'site_id' => $project->site_id,
                'factory_id' => $project->factory_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Add location tracking to tasks (which site or factory is this task at)
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('project_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('factory_id')->nullable()->after('site_id')->constrained('factories')->cascadeOnDelete();
        });

        // Migrate task locations from their parent projects
        // Each task inherits the location from its project
        DB::table('tasks')->get()->each(function ($task) {
            $projectLocation = DB::table('project_locations')
                ->where('project_id', $task->project_id)
                ->first();

            if ($projectLocation) {
                DB::table('tasks')->where('id', $task->id)->update([
                    'site_id' => $projectLocation->site_id,
                    'factory_id' => $projectLocation->factory_id,
                ]);
            }
        });

        // Remove site_id and factory_id from projects table
        // Projects are no longer tied to a single location
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropForeign(['factory_id']);
            $table->dropColumn(['site_id', 'factory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore site_id and factory_id to projects table
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('description')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('factory_id')->nullable()->after('site_id')->constrained('factories')->cascadeOnDelete();
        });

        // Remove location tracking from tasks
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropForeign(['factory_id']);
            $table->dropColumn(['site_id', 'factory_id']);
        });

        // Drop project_locations pivot table
        Schema::dropIfExists('project_locations');
    }
};
