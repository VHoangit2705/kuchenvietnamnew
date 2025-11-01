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
		// Target external order-related tables on connection mysql3
		if (Schema::connection('mysql3')->hasTable('orders')) {
			DB::connection('mysql3')->statement("ALTER TABLE `orders` CHANGE `order_code2` `order_code2` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
			DB::connection('mysql3')->statement("ALTER TABLE `orders` CHANGE `customer_phone` `customer_phone` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
			DB::connection('mysql3')->statement("ALTER TABLE `orders` CHANGE `agency_phone` `agency_phone` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
			DB::connection('mysql3')->statement("ALTER TABLE `orders` CHANGE `payment_method` `payment_method` VARCHAR(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL");
			DB::connection('mysql3')->statement("ALTER TABLE `orders` CHANGE `created_at` `created_at` TIMESTAMP NULL");
		}

		if (Schema::connection('mysql3')->hasTable('order_products')) {
			DB::connection('mysql3')->statement("ALTER TABLE `order_products` CHANGE `excluding_VAT` `excluding_VAT` INT(11) NULL");
		}

		if (Schema::connection('mysql3')->hasTable('installation_orders')) {
			DB::connection('mysql3')->statement("ALTER TABLE `installation_orders` CHANGE `created_at` `created_at` DATE NULL");
		}

		// Local project table on default connection
		if (Schema::hasTable('warranty_requests')) {
			DB::statement("ALTER TABLE `warranty_requests` CHANGE `return_date` `return_date` DATE NULL");
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		// Intentionally left blank because prior column definitions are not fully known.
		// If you need reversal logic, specify the exact previous types/nullability per column.
	}
};


