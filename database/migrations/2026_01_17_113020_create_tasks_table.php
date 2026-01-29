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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->cascadeOnDelete(); // for subtasks
            $table->string('code'); // e.g., "1.1", "1.2"
            $table->string('name'); // e.g., "Window", "Door"
            $table->text('description')->nullable();
            $table->string('section')->nullable(); // electrical, plumbing, masonry, painting, etc.
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'on_hold'])->default('pending');
            $table->integer('progress')->default(0); // 0-100
            $table->decimal('quoted_amount', 15, 2)->default(0);
            $table->decimal('labor_cost', 15, 2)->default(0); // calculated from work logs
            $table->decimal('material_cost', 15, 2)->default(0); // calculated from stock usage
            $table->decimal('actual_amount', 15, 2)->default(0); // labor_cost + material_cost
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
