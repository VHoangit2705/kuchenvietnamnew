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
        Schema::table('user_device_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('user_device_tokens', 'status')) {
                $table->string('status', 20)->default('approved')->after('is_active');
            }

            if (!Schema::hasColumn('user_device_tokens', 'approval_requested_at')) {
                $table->timestamp('approval_requested_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_device_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('user_device_tokens', 'approval_requested_at')) {
                $table->dropColumn('approval_requested_at');
            }

            if (Schema::hasColumn('user_device_tokens', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};


