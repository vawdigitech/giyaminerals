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
        Schema::table('site_issues', function (Blueprint $table) {
            // Make site_id nullable to support factory issues
            $table->foreignId('site_id')->nullable()->change();

            // Add factory_id field
            $table->foreignId('factory_id')->nullable()->after('site_id')->constrained('factories')->cascadeOnDelete();

            // Add index for factory issues
            $table->index(['factory_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_issues', function (Blueprint $table) {
            // Drop factory-related fields
            $table->dropIndex(['factory_id', 'status']);
            $table->dropForeign(['factory_id']);
            $table->dropColumn('factory_id');

            // Restore site_id as non-nullable
            $table->foreignId('site_id')->nullable(false)->change();
        });
    }
};
