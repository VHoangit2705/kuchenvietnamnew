<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Chuyển tất cả các cột VARCHAR trong bảng installation_orders 
     * từ utf8mb4_general_ci sang utf8mb4_unicode_ci
     */
    public function up(): void
    {
        if (Schema::connection('mysql3')->hasTable('installation_orders')) {
            // Danh sách các cột VARCHAR cần chuyển collation
            // (Cột product đã là utf8mb4_unicode_ci nên không cần thay đổi)
            $columns = [
                'order_code' => 'VARCHAR(255)',
                'full_name' => 'VARCHAR(255)',
                'phone_number' => 'VARCHAR(15)',
                'address' => 'VARCHAR(1024)',
                'reviews_install' => 'VARCHAR(255)',
                'agency_name' => 'VARCHAR(255)',
                'agency_phone' => 'VARCHAR(15)',
                'agency_payment' => 'VARCHAR(15)',
                'type' => 'VARCHAR(255)',
                'zone' => 'VARCHAR(255)',
            ];

            foreach ($columns as $column => $type) {
                DB::connection('mysql3')->statement(
                    "ALTER TABLE `installation_orders` 
                     CHANGE `{$column}` `{$column}` {$type} 
                     CHARACTER SET utf8mb4 
                     COLLATE utf8mb4_unicode_ci"
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     * Chuyển lại về utf8mb4_general_ci (nếu cần rollback)
     */
    public function down(): void
    {
        if (Schema::connection('mysql3')->hasTable('installation_orders')) {
            $columns = [
                'order_code' => 'VARCHAR(255)',
                'full_name' => 'VARCHAR(255)',
                'phone_number' => 'VARCHAR(15)',
                'address' => 'VARCHAR(1024)',
                'reviews_install' => 'VARCHAR(255)',
                'agency_name' => 'VARCHAR(255)',
                'agency_phone' => 'VARCHAR(15)',
                'agency_payment' => 'VARCHAR(15)',
                'type' => 'VARCHAR(255)',
                'zone' => 'VARCHAR(255)',
            ];

            foreach ($columns as $column => $type) {
                DB::connection('mysql3')->statement(
                    "ALTER TABLE `installation_orders` 
                     CHANGE `{$column}` `{$column}` {$type} 
                     CHARACTER SET utf8mb4 
                     COLLATE utf8mb4_general_ci"
                );
            }
        }
    }
};
