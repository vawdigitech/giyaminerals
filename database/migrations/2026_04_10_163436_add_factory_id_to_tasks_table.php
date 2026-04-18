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
        // Drop the foreign key constraint first
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        // Now drop the unique constraint
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'code']);
        });

        // Make changes to the table
        Schema::table('tasks', function (Blueprint $table) {
            // Make project_id nullable and re-add foreign key
            $table->foreignId('project_id')->nullable()->change();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();

            // Add factory_id - tasks can belong to either project (site-based) OR factory
            // Validation enforced at model level to ensure at least one is set
            $table->foreignId('factory_id')->nullable()->after('project_id')->constrained('factories')->cascadeOnDelete();

            // Note: Uniqueness of code within project/factory is enforced at model validation level
            // to handle the mutual exclusivity of project_id and factory_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['factory_id']);
            $table->dropColumn('factory_id');
        });

        // Drop the current foreign key on project_id
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        // Restore project_id to not nullable
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();

            // Restore the unique constraint
            $table->unique(['project_id', 'code']);
        });
    }
};
