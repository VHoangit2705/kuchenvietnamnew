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
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('device_fingerprint', 255)->unique();
            $table->string('device_token', 255)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('browser_info')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('device_token');
            $table->index('device_fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};

