<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('edit_ctv_history')) {
            return;
        }

        Schema::table('edit_ctv_history', function (Blueprint $table) {
            if (!Schema::hasColumn('edit_ctv_history', 'installation_orders_id')) {
                $table->unsignedBigInteger('installation_orders_id')->nullable()->after('order_code');
                $table->index('installation_orders_id');
            }
        });

        // Drop unique constraint on order_code to allow multiple history rows per order_code
        // Default name from Laravel: edit_ctv_history_order_code_unique
        try {
            Schema::table('edit_ctv_history', function (Blueprint $table) {
                $table->dropUnique('edit_ctv_history_order_code_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if index name differs or already dropped
        }

        // Ensure we still have an index for fast lookup by order_code
        try {
            Schema::table('edit_ctv_history', function (Blueprint $table) {
                $table->index('order_code');
            });
        } catch (\Throwable $e) {
            // Ignore if already exists
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('edit_ctv_history')) {
            return;
        }

        // Remove added index/column
        try {
            Schema::table('edit_ctv_history', function (Blueprint $table) {
                if (Schema::hasColumn('edit_ctv_history', 'installation_orders_id')) {
                    $table->dropIndex(['installation_orders_id']);
                    $table->dropColumn('installation_orders_id');
                }
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // Re-add unique constraint for rollback (best effort)
        try {
            Schema::table('edit_ctv_history', function (Blueprint $table) {
                $table->unique('order_code');
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }
};


