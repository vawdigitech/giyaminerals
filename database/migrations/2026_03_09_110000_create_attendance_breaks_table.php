<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                  ->constrained('attendances')
                  ->cascadeOnDelete();
            $table->timestamp('break_out_time');
            $table->timestamp('break_in_time')->nullable();
            $table->timestamps();

            $table->index('attendance_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['lunch_out_time', 'lunch_in_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');

        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('lunch_out_time')->nullable()->after('check_out_time');
            $table->timestamp('lunch_in_time')->nullable()->after('lunch_out_time');
        });
    }
};
