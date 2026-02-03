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

        Schema::connection($connection)->create('repair_guides', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('error_id')->index()
                  ->comment('ID lỗi thường gặp (common_errors.id)');
            $table->string('title')
                  ->comment('Tiêu đề hướng dẫn sửa chữa');
            $table->text('steps')->nullable()
                  ->comment('Các bước thực hiện (JSON hoặc text)');
            $table->unsignedInteger('estimated_time')->nullable()
                  ->comment('Thời gian ước tính (phút)');
            $table->text('safety_note')->nullable()
                  ->comment('Lưu ý an toàn');

            $table->timestamps();

            $table->foreign('error_id')
                  ->references('id')
                  ->on('common_errors')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'mysql';
        Schema::connection($connection)->dropIfExists('repair_guides');
    }
};
