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
        Schema::create('warranty_anomaly_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('staff_name', 255);
            $table->string('branch', 255);
            $table->date('date');
            $table->timestamp('blocked_until'); // Chặn đến khi nào (1 giờ sau)
            $table->integer('count_when_blocked'); // Số ca khi bị chặn
            $table->decimal('threshold', 10, 2); // Ngưỡng đã vượt
            $table->boolean('is_active')->default(1); // Còn hiệu lực không
            $table->timestamps();

            $table->index('staff_name');
            $table->index('branch');
            $table->index('date');
            $table->index('blocked_until');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_anomaly_blocks');
    }
};

