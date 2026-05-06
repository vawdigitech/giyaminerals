<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter from_type column to include 'factory'
        DB::statement("ALTER TABLE transfers MODIFY COLUMN from_type ENUM('warehouse', 'site', 'factory') NOT NULL");

        // Alter to_type column to include 'factory'
        DB::statement("ALTER TABLE transfers MODIFY COLUMN to_type ENUM('warehouse', 'site', 'factory') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert from_type column back to original enum values
        DB::statement("ALTER TABLE transfers MODIFY COLUMN from_type ENUM('warehouse', 'site') NOT NULL");

        // Revert to_type column back to original enum values
        DB::statement("ALTER TABLE transfers MODIFY COLUMN to_type ENUM('warehouse', 'site') NOT NULL");
    }
};
