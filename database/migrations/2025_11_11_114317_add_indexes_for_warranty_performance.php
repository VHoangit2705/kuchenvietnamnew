<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Thêm indexes để tối ưu performance cho các query warranty:
     * - serial_numbers.manhaphang: tối ưu subquery WHERE manhaphang = warranty_card.id
     * - serial_numbers.sn: tối ưu join và WHERE IN queries
     * - product_warranties.warranty_code: tối ưu JOIN product_warranties.warranty_code = serial_numbers.sn
     * - warranty_card.view: tối ưu WHERE view = ... queries
     */
    public function up(): void
    {
        // Kiểm tra và thêm index cho bảng serial_numbers trên connection mysql3
        if (Schema::connection('mysql3')->hasTable('serial_numbers')) {
            // Index cho manhaphang - tối ưu subquery WHERE manhaphang = warranty_card.id
            if (!$this->hasIndex('mysql3', 'serial_numbers', 'serial_numbers_manhaphang_index')) {
                Schema::connection('mysql3')->table('serial_numbers', function (Blueprint $table) {
                    $table->index('manhaphang', 'serial_numbers_manhaphang_index');
                });
            }
            
            // Index cho sn - tối ưu join và WHERE IN queries
            if (!$this->hasIndex('mysql3', 'serial_numbers', 'serial_numbers_sn_index')) {
                Schema::connection('mysql3')->table('serial_numbers', function (Blueprint $table) {
                    $table->index('sn', 'serial_numbers_sn_index');
                });
            }
            
            // Composite index cho (manhaphang, sn) - tối ưu queries kết hợp cả 2 cột
            if (!$this->hasIndex('mysql3', 'serial_numbers', 'serial_numbers_manhaphang_sn_index')) {
                Schema::connection('mysql3')->table('serial_numbers', function (Blueprint $table) {
                    $table->index(['manhaphang', 'sn'], 'serial_numbers_manhaphang_sn_index');
                });
            }
        }
        
        // Kiểm tra và thêm index cho bảng product_warranties trên connection mysql3
        if (Schema::connection('mysql3')->hasTable('product_warranties')) {
            // Index cho warranty_code - tối ưu JOIN product_warranties.warranty_code = serial_numbers.sn
            if (!$this->hasIndex('mysql3', 'product_warranties', 'product_warranties_warranty_code_index')) {
                Schema::connection('mysql3')->table('product_warranties', function (Blueprint $table) {
                    $table->index('warranty_code', 'product_warranties_warranty_code_index');
                });
            }
        }
        
        // Kiểm tra và thêm index cho bảng warranty_card trên connection mysql3
        if (Schema::connection('mysql3')->hasTable('warranty_card')) {
            // Index cho view - tối ưu WHERE view = ... queries
            if (!$this->hasIndex('mysql3', 'warranty_card', 'warranty_card_view_index')) {
                Schema::connection('mysql3')->table('warranty_card', function (Blueprint $table) {
                    $table->index('view', 'warranty_card_view_index');
                });
            }
            
            // Composite index cho (view, create_at) - tối ưu queries có filter view và sort create_at
            if (!$this->hasIndex('mysql3', 'warranty_card', 'warranty_card_view_create_at_index')) {
                Schema::connection('mysql3')->table('warranty_card', function (Blueprint $table) {
                    $table->index(['view', 'create_at'], 'warranty_card_view_create_at_index');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa indexes từ bảng serial_numbers
        if (Schema::connection('mysql3')->hasTable('serial_numbers')) {
            Schema::connection('mysql3')->table('serial_numbers', function (Blueprint $table) {
                $table->dropIndex('serial_numbers_manhaphang_sn_index');
                $table->dropIndex('serial_numbers_sn_index');
                $table->dropIndex('serial_numbers_manhaphang_index');
            });
        }
        
        // Xóa indexes từ bảng product_warranties
        if (Schema::connection('mysql3')->hasTable('product_warranties')) {
            Schema::connection('mysql3')->table('product_warranties', function (Blueprint $table) {
                $table->dropIndex('product_warranties_warranty_code_index');
            });
        }
        
        // Xóa indexes từ bảng warranty_card
        if (Schema::connection('mysql3')->hasTable('warranty_card')) {
            Schema::connection('mysql3')->table('warranty_card', function (Blueprint $table) {
                $table->dropIndex('warranty_card_view_create_at_index');
                $table->dropIndex('warranty_card_view_index');
            });
        }
    }
    
    /**
     * Kiểm tra xem index đã tồn tại chưa
     */
    private function hasIndex(string $connection, string $table, string $indexName): bool
    {
        try {
            $indexes = DB::connection($connection)
                ->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            // Nếu có lỗi (ví dụ: table không tồn tại), trả về false
            return false;
        }
    }
};
