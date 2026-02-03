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
        $connection = 'mysql';

        Schema::connection($connection)->create('common_errors', function (Blueprint $table) {
            $table->id();

            // FK logic → product_models.id (mysql3)
            $table->unsignedBigInteger('model_id')->index()
                  ->comment('ID model sản phẩm (product_models)');

            // Thông tin lỗi
            $table->string('error_code')->index()
                  ->comment('Mã lỗi kỹ thuật (VD: E01, E-LIDAR)');
            $table->string('error_name')
                  ->comment('Tên lỗi');
            $table->string('severity')->default('normal')
                  ->comment('normal | common | critical');
            $table->text('description')->nullable()
                  ->comment('Mô tả lỗi + điều kiện phát sinh');

            $table->timestamps();

            // 1 model không trùng mã lỗi
            $table->unique(
                ['model_id', 'error_code'],
                'uniq_model_error_code'
            );

            /**
             * FK DB-level (CÓ THỂ TẠO vì cùng mysql3)
             * Nếu product_models chắc chắn cũng ở mysql3
             */
            $table->foreign('model_id')
                  ->references('id')
                  ->on('product_models')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'mysql3';

        Schema::connection($connection)->dropIfExists('common_errors');
    }
};
