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
        Schema::create('warranty_request_details', function (Blueprint $table) {
            $table->id();
            $table->integer('warranty_request_id');
            $table->string('error_type')->nullable();
            $table->string('solution')->nullable();
            $table->string('replacement')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('unit_price')->nullable();
            $table->integer('total')->nullable();
            $table->text('Ngaytao');
            $table->string('edit_by')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_request_details');
    }
};
