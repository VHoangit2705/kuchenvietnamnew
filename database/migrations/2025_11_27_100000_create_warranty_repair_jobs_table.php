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
        Schema::create('warranty_repair_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warranty_request_id');
            $table->text('description');
            $table->string('component')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('total_price')->default(0);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('warranty_request_id')
                ->references('id')
                ->on('warranty_requests')
                ->onDelete('cascade');

            $table->index('warranty_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_repair_jobs');
    }
};

