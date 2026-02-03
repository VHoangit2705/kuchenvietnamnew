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

        Schema::connection($connection)->create('repair_guide_parts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('repair_guide_id')->index()
                  ->comment('ID hướng dẫn sửa (repair_guides.id)');
            $table->unsignedInteger('quantity')->default(1)
                  ->comment('Số lượng linh kiện');

            $table->timestamps();

            $table->foreign('repair_guide_id')
                  ->references('id')
                  ->on('repair_guides')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'mysql';
        Schema::connection($connection)->dropIfExists('repair_guide_parts');
    }
};
