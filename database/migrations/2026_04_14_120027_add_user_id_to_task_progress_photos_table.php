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
        Schema::table('task_progress_photos', function (Blueprint $table) {
            // Add user_id to track all uploads (both employees and regular users)
            $table->foreignId('user_id')->after('task_id')->constrained('users')->cascadeOnDelete();

            // Make employee_id nullable since not all users are employees
            $table->foreignId('employee_id')->nullable()->change();

            // Add index for efficient querying by user
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_progress_photos', function (Blueprint $table) {
            // Drop the user_id foreign key and column
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');

            // Make employee_id not nullable again
            $table->foreignId('employee_id')->nullable(false)->change();
        });
    }
};
