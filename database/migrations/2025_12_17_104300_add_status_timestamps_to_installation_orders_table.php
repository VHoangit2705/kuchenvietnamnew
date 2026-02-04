<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection('mysql3')->hasTable('installation_orders')) {
            // Thêm cột dispatched_at - Thời gian chuyển sang "Đã điều phối" (status_install = 1)
            if (!Schema::connection('mysql3')->hasColumn('installation_orders', 'dispatched_at')) {
                DB::connection('mysql3')->statement("ALTER TABLE `installation_orders` ADD `dispatched_at` DATETIME NULL DEFAULT NULL AFTER `status_install`");
            }
            
            // Thêm cột paid_at - Thời gian chuyển sang "Đã thanh toán" (status_install = 3)
            if (!Schema::connection('mysql3')->hasColumn('installation_orders', 'paid_at')) {
                DB::connection('mysql3')->statement("ALTER TABLE `installation_orders` ADD `paid_at` DATETIME NULL DEFAULT NULL AFTER `successed_at`");
            }
            
            // Thêm cột agency_at - Thời gian chuyển sang "Đại lý lắp đặt"
            if (!Schema::connection('mysql3')->hasColumn('installation_orders', 'agency_at')) {
                DB::connection('mysql3')->statement("ALTER TABLE `installation_orders` ADD `agency_at` DATETIME NULL DEFAULT NULL AFTER `paid_at`");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('mysql3')->hasTable('installation_orders')) {
            if (Schema::connection('mysql3')->hasColumn('installation_orders', 'dispatched_at')) {
                DB::connection('mysql3')->statement("ALTER TABLE `installation_orders` DROP COLUMN `dispatched_at`");
            }
            if (Schema::connection('mysql3')->hasColumn('installation_orders', 'paid_at')) {
                DB::connection('mysql3')->statement("ALTER TABLE `installation_orders` DROP COLUMN `paid_at`");
            }
            if (Schema::connection('mysql3')->hasColumn('installation_orders', 'agency_at')) {
                DB::connection('mysql3')->statement("ALTER TABLE `installation_orders` DROP COLUMN `agency_at`");
            }
        }
    }
};
