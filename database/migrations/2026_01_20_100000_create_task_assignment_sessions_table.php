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
        Schema::create('task_assignment_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignment_id')->constrained('task_assignments')->cascadeOnDelete();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->decimal('hours', 8, 2)->default(0); // auto-calculated
            $table->enum('end_reason', ['checkout', 'removed', 'day_end'])->nullable();
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending');
            $table->timestamps();

            // An assignment can only have one active session per day per attendance
            $table->index(['task_assignment_id', 'date']);
            $table->index(['attendance_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignment_sessions');
    }
};
