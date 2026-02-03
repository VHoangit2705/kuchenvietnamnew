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

        Schema::connection($connection)->create('warranty_cases', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('model_id')->index()
                  ->comment('ID model sản phẩm (product_models - logic FK)');
            $table->unsignedBigInteger('error_id')->index()
                  ->comment('ID lỗi (common_errors.id)');
            $table->unsignedBigInteger('repair_guide_id')->nullable()->index()
                  ->comment('ID hướng dẫn sửa đã áp dụng (repair_guides.id)');
            $table->unsignedBigInteger('technician_id')->nullable()
                  ->comment('ID kỹ thuật viên xử lý');
            $table->string('result')->nullable()
                  ->comment('Kết quả xử lý: resolved | partial | failed | ...');
            $table->text('note')->nullable()
                  ->comment('Ghi chú');

            $table->timestamps();

            $table->foreign('error_id')
                  ->references('id')
                  ->on('common_errors')
                  ->onDelete('cascade');
            $table->foreign('repair_guide_id')
                  ->references('id')
                  ->on('repair_guides')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'mysql';
        Schema::connection($connection)->dropIfExists('warranty_cases');
    }
};
