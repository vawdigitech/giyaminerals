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
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('task_id')->nullable()->after('product_id')->constrained('tasks')->nullOnDelete();
            $table->foreignId('task_stock_usage_id')->nullable()->after('task_id')->constrained('task_stock_usages')->nullOnDelete();
            $table->string('transfer_type')->default('transfer')->after('transfer_date'); // 'transfer' or 'return'
            $table->text('notes')->nullable()->after('transfer_type');
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropForeign(['task_stock_usage_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['task_id', 'task_stock_usage_id', 'transfer_type', 'notes', 'created_by']);
        });
    }
};
