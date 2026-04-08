<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('lunch_out_time')->nullable()->after('check_out_time');
            $table->timestamp('lunch_in_time')->nullable()->after('lunch_out_time');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['lunch_out_time', 'lunch_in_time']);
        });
    }
};
