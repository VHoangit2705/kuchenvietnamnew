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
        $connection = 'mysql3';

        Schema::connection($connection)->create('product_models', function (Blueprint $table) {
            $table->id();

            // FK → products.id
            $table->unsignedBigInteger('product_id')->index()
                  ->comment('ID sản phẩm cha');

            // Thông tin model
            $table->string('model_code')->index()
                  ->comment('Mã model kỹ thuật (VD: KU-8881)');
            $table->string('version')->nullable()
                  ->comment('Phiên bản model (V1, V2, 2024...)');
            $table->integer('release_year')->nullable()
                  ->comment('Năm phát hành');
            $table->string('xuat_xu')->nullable()
                  ->comment('Xuất xứ sản phẩm');

            // Trạng thái
            $table->string('status')->default('active')
                  ->comment('active | inactive | discontinued');

            $table->timestamps();

            // Unique: không trùng model + version trong cùng sản phẩm
            $table->unique(
                ['product_id', 'model_code', 'version'],
                'uniq_product_model_version'
            );

            // Foreign key
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = 'mysql3';

        Schema::connection($connection)->dropIfExists('product_models');
    }
};
