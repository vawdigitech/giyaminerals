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
        Schema::table('weekly_salary_payments', function (Blueprint $table) {
            $table->decimal('advance_deducted', 12, 2)->default(0)->after('total_salary');
            $table->decimal('net_salary', 12, 2)->default(0)->after('advance_deducted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_salary_payments', function (Blueprint $table) {
            $table->dropColumn(['advance_deducted', 'net_salary']);
        });
    }
};
