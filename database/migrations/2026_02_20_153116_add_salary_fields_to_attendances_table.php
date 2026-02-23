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
            $table->decimal('daily_salary', 10, 2)->nullable()->after('total_hours');
            $table->decimal('hourly_rate_at_time', 10, 2)->nullable()->after('daily_salary');
            $table->decimal('daily_rate_at_time', 10, 2)->nullable()->after('hourly_rate_at_time');
            $table->enum('salary_type_at_time', ['daily', 'hourly'])->nullable()->after('daily_rate_at_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'daily_salary',
                'hourly_rate_at_time',
                'daily_rate_at_time',
                'salary_type_at_time',
            ]);
        });
    }
};
