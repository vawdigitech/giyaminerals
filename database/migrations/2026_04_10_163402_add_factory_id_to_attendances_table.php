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
            // Make site_id nullable since attendance can be at factory instead
            $table->foreignId('site_id')->nullable()->change();

            // Add factory_id - attendance can be marked at either site OR factory
            // Validation enforced at model level to ensure at least one is set
            $table->foreignId('factory_id')->nullable()->after('site_id')->constrained('factories')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['factory_id']);
            $table->dropColumn('factory_id');

            // Restore site_id to not nullable
            $table->foreignId('site_id')->nullable(false)->change();
        });
    }
};
