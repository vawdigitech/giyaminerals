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
        Schema::create('task_progress_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->longText('photo'); // Base64 encoded image
            $table->string('caption')->nullable(); // Optional description
            $table->date('captured_date'); // Date of the progress photo
            $table->timestamps();

            // Index for efficient querying by task and date
            $table->index(['task_id', 'captured_date']);
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_progress_photos');
    }
};
