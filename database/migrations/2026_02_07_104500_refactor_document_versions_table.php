<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->table('document_versions', function (Blueprint $table) {
            $table->string('img_upload')->nullable()->after('version');
            $table->string('video_upload')->nullable()->after('img_upload');
            $table->string('pdf_upload')->nullable()->after('video_upload');
        });

        // Migrate existing data
        $versions = DB::connection('mysql')->table('document_versions')->get();
        foreach ($versions as $version) {
            if (empty($version->file_path)) {
                continue;
            }

            $updateData = [];
            $path = $version->file_path;
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $updateData['img_upload'] = $path;
            } elseif (in_array($extension, ['mp4', 'webm', 'mov', 'avi'])) {
                $updateData['video_upload'] = $path;
            } elseif ($extension === 'pdf') {
                $updateData['pdf_upload'] = $path;
            }

            if (!empty($updateData)) {
                DB::connection('mysql')->table('document_versions')
                    ->where('id', $version->id)
                    ->update($updateData);
            }
        }

        Schema::connection('mysql')->table('document_versions', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql')->table('document_versions', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('version');
            $table->string('file_type')->nullable()->after('file_path');
        });

        // Restore data (best effort)
        $versions = DB::table('document_versions')->get();
        foreach ($versions as $version) {
            $filePath = $version->img_upload ?? $version->video_upload ?? $version->pdf_upload;
            if ($filePath) {
                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                DB::table('document_versions')
                    ->where('id', $version->id)
                    ->update([
                        'file_path' => $filePath,
                        'file_type' => $extension
                    ]);
            }
        }

        Schema::connection('mysql')->table('document_versions', function (Blueprint $table) {
            $table->dropColumn(['img_upload', 'video_upload', 'pdf_upload']);
        });
    }
};
