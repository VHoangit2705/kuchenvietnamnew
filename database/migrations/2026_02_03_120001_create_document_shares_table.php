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

        Schema::connection($connection)->create('document_shares', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('document_version_id')->index()
                  ->comment('ID phiên bản tài liệu (document_versions.id)');
            $table->string('share_token')->unique()
                  ->comment('Token chia sẻ');
            $table->string('permission')->default('view')
                  ->comment('view | download | edit');
            $table->string('password_hash')->nullable()
                  ->comment('Mật khẩu bảo vệ (hash)');
            $table->dateTime('expires_at')->nullable()
                  ->comment('Hết hạn chia sẻ');
            $table->unsignedInteger('access_count')->default(0)
                  ->comment('Số lần truy cập');
            $table->dateTime('last_access_at')->nullable()
                  ->comment('Lần truy cập cuối');
            $table->unsignedBigInteger('created_by')->nullable()
                  ->comment('ID user tạo');
            $table->string('status')->default('active')
                  ->comment('active | expired | revoked');

            $table->timestamps();

            $table->foreign('document_version_id')
                  ->references('id')
                  ->on('document_versions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'mysql';
        Schema::connection($connection)->dropIfExists('document_shares');
    }
};
