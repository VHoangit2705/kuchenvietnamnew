<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng lưu snapshot dữ liệu báo cáo để khóa cứng số liệu tại thời điểm tính toán
     */
    public function up(): void
    {
        Schema::create('warranty_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->enum('report_type', ['weekly', 'monthly'])->comment('Loại báo cáo: hàng tuần hoặc hàng tháng');
            $table->dateTime('from_date')->comment('Ngày bắt đầu khoảng thời gian báo cáo (00:00 thứ 2)');
            $table->dateTime('to_date')->comment('Ngày kết thúc khoảng thời gian báo cáo (23:59 thứ 2 tuần sau)');
            $table->dateTime('snapshot_date')->comment('Thời điểm lưu snapshot (23:59 thứ 2)');
            $table->string('branch')->default('all')->comment('Chi nhánh hoặc all cho tổng hợp');
            $table->longText('warranty_data')->comment('JSON chứa danh sách warranty_requests');
            $table->longText('work_process_data')->comment('JSON chứa thống kê quy trình làm việc');
            $table->json('summary_data')->nullable()->comment('JSON chứa tổng hợp nhanh');
            $table->boolean('is_sent')->default(false)->comment('Đã gửi email hay chưa');
            $table->dateTime('sent_at')->nullable()->comment('Thời điểm gửi email');
            $table->timestamps();

            // Index để tối ưu query
            $table->index(['report_type', 'from_date', 'to_date'], 'snapshot_report_date_index');
            $table->index(['branch', 'snapshot_date'], 'snapshot_branch_date_index');
            $table->index(['is_sent', 'report_type'], 'snapshot_sent_type_index');
            
            // Unique constraint để tránh duplicate snapshot
            $table->unique(['report_type', 'from_date', 'to_date', 'branch'], 'snapshot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_report_snapshots');
    }
};
