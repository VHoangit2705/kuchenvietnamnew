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
        Schema::create('warranty_requests', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->nullable();
            $table->string('serial_thanmay')->nullable();
            $table->string('product')->nullable();
            $table->string('full_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('staff_received')->nullable();
            $table->string('received_date')->nullable();
            $table->string('branch')->nullable();
            $table->string('return_date')->nullable();
            $table->string('shipment_date')->nullable();
            $table->date('initial_fault_condition')->nullable();
            $table->date('product_fault_condition')->nullable();
            $table->text('product_quantity_description')->nullable();
            $table->text('image_upload')->nullable();
            $table->text('video_upload')->nullable();
            $table->text('type')->nullable();
            $table->text('save_img')->nullable();
            $table->text('save_video')->nullable();
            $table->text('Ngaytao');
            $table->text('status')->nullable();
            $table->text('view')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_requests');
    }
};
