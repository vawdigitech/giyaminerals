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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., "1.0", "2.0"
            $table->string('name'); // e.g., "Master Bedroom", "Kitchen"
            $table->text('description')->nullable();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->decimal('quoted_amount', 15, 2)->default(0);
            $table->decimal('actual_amount', 15, 2)->default(0); // calculated from tasks
            $table->enum('status', ['pending', 'in_progress', 'completed', 'on_hold'])->default('pending');
            $table->integer('progress')->default(0); // 0-100
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
