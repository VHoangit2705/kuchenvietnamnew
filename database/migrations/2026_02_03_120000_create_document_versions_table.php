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

        Schema::connection($connection)->create('document_versions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('document_id')->index()
                  ->comment('ID tài liệu kỹ thuật (technical_documents.id)');
            $table->string('version')
                  ->comment('Phiên bản tài liệu (VD: 1.0, 2.0)');
            $table->string('file_path')
                  ->comment('Đường dẫn file');
            $table->string('file_type')->nullable()
                  ->comment('Loại file: pdf, doc, image, ...');
            $table->string('status')->default('active')
                  ->comment('active | inactive | deprecated');
            $table->unsignedBigInteger('uploaded_by')->nullable()
                  ->comment('ID user upload');

            $table->timestamps();

            $table->foreign('document_id')
                  ->references('id')
                  ->on('technical_documents')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'mysql';
        Schema::connection($connection)->dropIfExists('document_versions');
    }
};
