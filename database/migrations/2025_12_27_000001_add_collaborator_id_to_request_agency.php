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
        Schema::table('request_agency', function (Blueprint $table) {
            if (!Schema::hasColumn('request_agency', 'collaborator_id')) {
                $table->unsignedInteger('collaborator_id')
                    ->nullable()
                    ->after('agency_id')
                    ->index();

                $table->foreign('collaborator_id')
                    ->references('id')
                    ->on('warranty_collaborator')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_agency', function (Blueprint $table) {
            if (Schema::hasColumn('request_agency', 'collaborator_id')) {
                $table->dropForeign(['collaborator_id']);
                $table->dropColumn('collaborator_id');
            }
        });
    }
};
