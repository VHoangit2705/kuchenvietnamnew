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

        Schema::connection($connection)->create('repair_guide_documents', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('repair_guide_id')->index()
                  ->comment('ID hướng dẫn sửa (repair_guides.id)');
            $table->unsignedBigInteger('document_id')->index()
                  ->comment('ID tài liệu kỹ thuật (technical_documents.id)');

            $table->timestamps();

            $table->unique(['repair_guide_id', 'document_id'], 'uniq_repair_guide_document');

            $table->foreign('repair_guide_id')
                  ->references('id')
                  ->on('repair_guides')
                  ->onDelete('cascade');
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
        Schema::connection($connection)->dropIfExists('repair_guide_documents');
    }
};
