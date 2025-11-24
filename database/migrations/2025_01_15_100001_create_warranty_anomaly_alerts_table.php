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
        Schema::create('warranty_anomaly_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('branch', 255);
            $table->string('staff_name', 255);
            $table->date('date');
            $table->integer('staff_count'); // Số ca nhân viên này nhận
            $table->integer('total_count'); // Tổng số ca của kho
            $table->integer('staff_count_in_branch'); // Số nhân viên trong kho
            $table->decimal('average_count', 10, 2); // Số ca trung bình
            $table->decimal('threshold', 10, 2); // Ngưỡng cảnh báo (average * 2.5)
            $table->enum('alert_level', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('is_resolved')->default(0);
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('branch');
            $table->index('date');
            $table->index('staff_name');
            $table->index('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_anomaly_alerts');
    }
};

