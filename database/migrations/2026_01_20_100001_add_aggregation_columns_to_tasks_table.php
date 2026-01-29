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
        Schema::table('tasks', function (Blueprint $table) {
            // Aggregation columns for master tasks (sum of subtask values)
            $table->decimal('total_hours_worked', 15, 2)->default(0)->after('actual_amount');
            $table->decimal('aggregated_labor_cost', 15, 2)->default(0)->after('total_hours_worked');
            $table->decimal('aggregated_material_cost', 15, 2)->default(0)->after('aggregated_labor_cost');
            $table->decimal('aggregated_actual_amount', 15, 2)->default(0)->after('aggregated_material_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'total_hours_worked',
                'aggregated_labor_cost',
                'aggregated_material_cost',
                'aggregated_actual_amount',
            ]);
        });
    }
};
