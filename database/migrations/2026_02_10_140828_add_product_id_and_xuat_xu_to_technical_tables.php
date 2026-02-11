<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('technical_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('technical_documents', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('technical_documents', 'xuat_xu')) {
                $table->string('xuat_xu')->nullable()->after('product_id');
            }
        });

        Schema::table('common_errors', function (Blueprint $table) {
            if (!Schema::hasColumn('common_errors', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('common_errors', 'xuat_xu')) {
                $table->string('xuat_xu')->nullable()->after('product_id');
            }
            if (Schema::hasColumn('common_errors', 'model_id')) {
                $table->dropColumn('model_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technical_documents', function (Blueprint $table) {
            $table->dropColumn(['product_id', 'xuat_xu']);
        });

        Schema::table('common_errors', function (Blueprint $table) {
            $table->dropColumn(['product_id', 'xuat_xu']);
            $table->unsignedBigInteger('model_id')->nullable()->after('id');
        });
    }
};
