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
            
            $table->foreign('warranty_request_id')
                  ->references('id')
                  ->on('warranty_requests')
                  ->onDelete('cascade');
            
            // Cho phép nhiều bản ghi cho cùng một warranty_request_id
            // và thêm index thường để tối ưu truy vấn.
            $table->index('warranty_request_id', 'warranty_upload_error_request_id_index');
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

