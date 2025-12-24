<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tạo bảng trong database kho_kuchen (connection mysql3)
        Schema::connection('mysql3')->create('user_agency', function (Blueprint $table) {
            $table->id();

            // 1. Username là số điện thoại (Unique để không trùng)
            $table->string('username', 20)->unique()->comment('Số điện thoại đăng nhập');

            // 2. Mật khẩu (Lưu chuỗi MD5)
            $table->string('password'); 

            // 3. Họ tên đầy đủ
            $table->string('fullname');

            // 4. Thời gian xác minh (Nullable vì mới tạo chưa xác minh)
            $table->timestamp('phone_verified_at')->nullable();

            // 5. Trạng thái (0: Chưa xác minh, 1: Đã xác minh, 2: Khóa...)
            $table->tinyInteger('status')->default(0)->comment('0: Unverified, 1: Verified');

            // 6. OTP Zalo và Thời gian hết hạn OTP (Bổ sung quan trọng)
            $table->string('otp_oa', 10)->nullable();
            $table->timestamp('otp_expires_at')->nullable()->comment('Thời gian OTP hết hạn');

            // 7. Token ghi nhớ đăng nhập (Cho chức năng "Remember me")
            $table->rememberToken();

            // 8. Timestamps tạo tự động 2 cột: created_at và updated_at
            // Lưu ý: Laravel mặc định là "created_at" (có chữ d). 
            // Nếu dự án BẮT BUỘC dùng "create_at", hãy xem ghi chú bên dưới.
            $table->timestamps(); 
        });
    }

    public function down()
    {
        // Xóa bảng từ database kho_kuchen (connection mysql3)
        Schema::connection('mysql3')->dropIfExists('user_agency');
    }
};