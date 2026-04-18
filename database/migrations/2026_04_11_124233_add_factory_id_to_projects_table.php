<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add factory_id to projects table
        Schema::table('projects', function (Blueprint $table) {
            // Make site_id nullable (projects can belong to site OR factory)
            $table->foreignId('site_id')->nullable()->change();

            // Add factory_id
            $table->foreignId('factory_id')->nullable()->after('site_id')
                ->constrained('factories')->cascadeOnDelete();
        });

        // Remove factory_id from tasks table (tasks only belong to projects)
        Schema::table('tasks', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['factory_id']);
            // Then drop column
            $table->dropColumn('factory_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore factory_id to tasks
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('factory_id')->nullable()->after('project_id')
                ->constrained('factories')->cascadeOnDelete();
        });

        // Remove factory_id from projects
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['factory_id']);
            $table->dropColumn('factory_id');

            // Make site_id required again
            $table->foreignId('site_id')->nullable(false)->change();
        });
    }
};
