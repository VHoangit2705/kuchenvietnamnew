<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('warranty_collaborator', 'bank_name')) {
            Schema::table('warranty_collaborator', function (Blueprint $table) {
                $table->string('bank_name', 50)->nullable()->after('ward_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('warranty_collaborator', 'bank_name')) {
            Schema::table('warranty_collaborator', function (Blueprint $table) {
                $table->dropColumn('bank_name');
            });
        }
    }
};


