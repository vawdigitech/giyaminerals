<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE task_assignment_sessions MODIFY end_reason ENUM('checkout','removed','day_end','break') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE task_assignment_sessions MODIFY end_reason ENUM('checkout','removed','day_end') NULL");
    }
};
