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

        Schema::connection($connection)->create('technical_document_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('file_path');
            $table->string('file_type', 50)->nullable();
            $table->string('file_name')->nullable();
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('technical_documents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'mysql1';
        Schema::connection($connection)->dropIfExists('technical_document_attachments');
    }
};
