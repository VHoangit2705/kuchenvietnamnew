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
        Schema::create('warranty_overdue_rate_history', function (Blueprint $table) {
            $table->id();
            $table->date('report_date'); // Ngày báo cáo
            $table->enum('report_type', ['weekly', 'monthly']); // Loại báo cáo: hàng tuần hoặc hàng tháng
            $table->date('from_date'); // Ngày bắt đầu khoảng thời gian báo cáo
            $table->date('to_date'); // Ngày kết thúc khoảng thời gian báo cáo
            $table->string('branch')->nullable(); // Chi nhánh (nullable để có tổng hợp toàn hệ thống)
            $table->string('staff_received')->nullable(); // Kỹ thuật viên (nullable để có tổng hợp theo chi nhánh)
            $table->integer('tong_tiep_nhan')->default(0); // Tổng số ca tiếp nhận
            $table->integer('so_ca_qua_han')->default(0); // Số ca quá hạn
            $table->decimal('ti_le_qua_han', 5, 2)->default(0); // Tỉ lệ quá hạn (%)
            $table->integer('dang_sua_chua')->default(0); // Số ca đang sửa chữa
            $table->integer('cho_khach_hang_phan_hoi')->default(0); // Số ca chờ KH phản hồi
            $table->integer('da_hoan_tat')->default(0); // Số ca đã hoàn tất
            $table->timestamps();

            // Index để tối ưu query
            $table->index(['report_date', 'report_type']);
            $table->index(['branch', 'report_date']);
            $table->index(['staff_received', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_overdue_rate_history');
    }
};

