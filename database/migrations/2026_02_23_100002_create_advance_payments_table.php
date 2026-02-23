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
        Schema::create('advance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('week_start_date');      // Saturday (links to week period)
            $table->date('week_end_date');        // Friday
            $table->decimal('amount', 12, 2);     // Advance amount given
            $table->decimal('earned_salary_at_time', 12, 2); // Snapshot of earned salary when advance was given
            $table->integer('days_worked_at_time'); // Snapshot of days worked when advance was given
            $table->enum('status', ['given', 'deducted', 'cancelled'])->default('given');
            $table->timestamp('given_at');        // When advance was given
            $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deducted_at')->nullable(); // When deducted from salary
            $table->foreignId('weekly_salary_payment_id')->nullable()->constrained('weekly_salary_payments')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'week_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advance_payments');
    }
};
