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
        Schema::create('edit_ctv_history', function (Blueprint $table) {
            $table->id();
            
            // Thêm các trường mới cho collaborator_id
            $table->integer('old_collaborator_id')->nullable();
            $table->integer('new_collaborator_id')->nullable();
            
            $table->string('action_type'); // 'create', 'update', 'delete', 'update_agency'
            $table->string('edited_by'); // Người thực hiện thay đổi
            $table->text('comments')->nullable(); // Ghi chú
            $table->timestamp('edited_at');
            
            // Thông tin CTV - Cũ và Mới
            $table->string('old_full_name')->nullable();
            $table->string('new_full_name')->nullable();
            $table->string('old_phone')->nullable();
            $table->string('new_phone')->nullable();
            $table->string('old_province')->nullable();
            $table->string('new_province')->nullable();
            $table->string('old_province_id')->nullable();
            $table->string('new_province_id')->nullable();
            $table->string('old_district')->nullable();
            $table->string('new_district')->nullable();
            $table->string('old_district_id')->nullable();
            $table->string('new_district_id')->nullable();
            $table->string('old_ward')->nullable();
            $table->string('new_ward')->nullable();
            $table->string('old_ward_id')->nullable();
            $table->string('new_ward_id')->nullable();
            $table->text('old_address')->nullable();
            $table->text('new_address')->nullable();
            
            // Thông tin tài khoản CTV - Cũ và Mới
            $table->string('old_sotaikhoan')->nullable();
            $table->string('new_sotaikhoan')->nullable();
            $table->string('old_chinhanh')->nullable();
            $table->string('new_chinhanh')->nullable();
            $table->string('old_cccd')->nullable();
            $table->string('new_cccd')->nullable();
            $table->date('old_ngaycap')->nullable();
            $table->date('new_ngaycap')->nullable();
            
            // Thông tin đại lý - Cũ và Mới
            $table->string('old_agency_name')->nullable();
            $table->string('new_agency_name')->nullable();
            $table->string('old_agency_phone')->nullable();
            $table->string('new_agency_phone')->nullable();
            $table->text('old_agency_address')->nullable();
            $table->text('new_agency_address')->nullable();
            $table->string('old_agency_paynumber')->nullable();
            $table->string('new_agency_paynumber')->nullable();
            $table->string('old_agency_branch')->nullable();
            $table->string('new_agency_branch')->nullable();
            $table->string('old_agency_cccd')->nullable();
            $table->string('new_agency_cccd')->nullable();
            $table->date('old_agency_release_date')->nullable();
            $table->date('new_agency_release_date')->nullable();
            
            // Mã đơn hàng để kiểm tra và cập nhật
            $table->string('order_code')->nullable();
            
            // Unique index để đảm bảo mỗi order_code chỉ có 1 dòng log tổng hợp
            $table->unique('order_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edit_ctv_history');
    }
};
