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
        Schema::create('warranty_upload_error', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warranty_request_id');
            $table->text('video_upload_error')->nullable();
            $table->text('image_upload_error')->nullable();
            $table->text('note_error')->nullable();
            $table->timestamps();

            $table->index('warranty_request_id');
            // FK bỏ tạm do errno 150 (warranty_requests.id có thể khác kiểu). Thêm sau khi kiểm tra: SHOW CREATE TABLE warranty_requests;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_upload_error');
    }
};

