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
        Schema::connection('mysql')->create('request_agency', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 100)->comment('Mã đơn hàng');
            $table->string('product_name', 255)->comment('Tên sản phẩm');
            $table->string('customer_name', 255)->comment('Họ tên khách hàng');
            $table->string('customer_phone', 20)->comment('Số điện thoại khách hàng');
            $table->text('installation_address')->comment('Địa chỉ lắp đặt');
            $table->text('notes')->nullable()->comment('Ghi chú thêm');
            $table->string('status', 50)->default('chua_tiep_nhan')->comment('Trạng thái: chua_tiep_nhan, da_tiep_nhan, da_dieu_phoi');
            // type: 0 = Đại lý tự lắp đặt, 1 = Kuchen cử nhân viên lắp đặt
            $table->tinyInteger('type')->default(0); //0: Đại lý tự lắp đặt, 1: Kuchen cử nhân viên lắp đặt
            $table->unsignedBigInteger('agency_id')->nullable()->comment('ID đại lý (tham chiếu bảng agency)');
            $table->timestamp('received_at')->nullable()->comment('Ngày giờ tiếp nhận');
            $table->string('received_by', 255)->nullable()->comment('Người tiếp nhận');
            $table->string('assigned_to', 255)->nullable()->comment('Người được gán xử lý');
            $table->timestamps();
            
            // Indexes
            $table->index('order_code');
            $table->index('status');
            $table->index('customer_phone');
            $table->index('created_at');
            $table->index('agency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('request_agency');
    }
};
