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
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('overtime_hours', 5, 2)->nullable()->default(0)->after('daily_salary');
            $table->decimal('overtime_salary', 10, 2)->nullable()->default(0)->after('overtime_hours');
            $table->boolean('overtime_applicable')->default(false)->after('overtime_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'overtime_hours',
                'overtime_salary',
                'overtime_applicable',
            ]);
        });
    }
};
