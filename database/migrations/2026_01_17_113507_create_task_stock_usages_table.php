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
        Schema::create('task_stock_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete(); // source stock
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2)->default(0); // price per unit at time of usage
            $table->decimal('total_cost', 15, 2)->default(0); // quantity * unit_price
            $table->text('notes')->nullable();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at');
            $table->timestamps();

            $table->index(['task_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_stock_usages');
    }
};
