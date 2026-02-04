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

        Schema::connection($connection)->create('technical_documents', function (Blueprint $table) {
            $table->id();

            // FK logic tới product_models (mysql3)
            $table->unsignedBigInteger('model_id')->index()
                  ->comment('ID model sản phẩm (tham chiếu product_models.mysql3)');

            // Thông tin tài liệu
            $table->string('doc_type')
                  ->comment('manual | wiring | repair | image | video | bulletin');
            $table->string('title')
                  ->comment('Tiêu đề tài liệu');
            $table->text('description')->nullable()
                  ->comment('Mô tả nội dung tài liệu');

            $table->string('status')->default('active')
                  ->comment('active | inactive | deprecated');

            $table->timestamps();

            /**
             * ⚠️ LƯU Ý QUAN TRỌNG
             * Không tạo foreign key DB-level vì khác connection (mysql1 ↔ mysql3)
             * Ràng buộc FK sẽ được kiểm soát ở tầng application
             */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'mysql1';

        Schema::connection($connection)->dropIfExists('technical_documents');
    }
};
