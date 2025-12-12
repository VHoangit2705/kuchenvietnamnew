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
        // Thêm cột agency_id và foreign key vào bảng user_agency
        Schema::connection('mysql3')->table('user_agency', function (Blueprint $table) {
            // Thêm cột agency_id (nullable vì có thể có user chưa được gán agency)
            $table->unsignedBigInteger('agency_id')->nullable()->after('id')->comment('ID đại lý liên kết');
            
            // Thêm foreign key constraint
            $table->foreign('agency_id')
                  ->references('id')
                  ->on('agency')
                  ->onDelete('set null') // Nếu agency bị xóa, set agency_id = null
                  ->onUpdate('cascade'); // Nếu agency.id thay đổi, cập nhật theo
            
            // Thêm index cho agency_id để tối ưu query
            $table->index('agency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql3')->table('user_agency', function (Blueprint $table) {
            // Xóa foreign key trước
            $table->dropForeign(['agency_id']);
            
            // Xóa index
            $table->dropIndex(['agency_id']);
            
            // Xóa cột
            $table->dropColumn('agency_id');
        });
    }
};
