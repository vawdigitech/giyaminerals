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
        Schema::table('stock_entries', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('received_quantity', 12, 3)->default(0)->change();
            $table->decimal('transferred_quantity', 12, 3)->default(0)->change();
            $table->decimal('balance', 12, 3)->default(0)->change();
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
        });

        Schema::table('task_stock_usages', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
        });

        if (Schema::hasTable('warehouse_stocks')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->decimal('quantity', 12, 3)->default(0)->change();
            });
        }

        if (Schema::hasTable('site_stocks')) {
            Schema::table('site_stocks', function (Blueprint $table) {
                $table->decimal('quantity', 12, 3)->default(0)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_entries', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->integer('received_quantity')->default(0)->change();
            $table->integer('transferred_quantity')->default(0)->change();
            $table->integer('balance')->default(0)->change();
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('task_stock_usages', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->change();
        });

        if (Schema::hasTable('warehouse_stocks')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->integer('quantity')->default(0)->change();
            });
        }

        if (Schema::hasTable('site_stocks')) {
            Schema::table('site_stocks', function (Blueprint $table) {
                $table->integer('quantity')->default(0)->change();
            });
        }
    }
};
