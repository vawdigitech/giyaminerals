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
        Schema::table('employees', function (Blueprint $table) {
            // Add separate wage fields for factory work
            // These will be used when employee marks attendance at a factory
            $table->decimal('factory_daily_rate', 10, 2)->nullable()->after('working_hours');
            $table->decimal('factory_hourly_rate', 10, 2)->nullable()->after('factory_daily_rate');
            $table->decimal('factory_working_hours', 5, 2)->nullable()->after('factory_hourly_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['factory_daily_rate', 'factory_hourly_rate', 'factory_working_hours']);
        });
    }
};
