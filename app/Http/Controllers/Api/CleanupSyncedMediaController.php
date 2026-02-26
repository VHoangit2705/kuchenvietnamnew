<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanupSyncedMediaController extends Controller
{
    public function run()
    {
        set_time_limit(300);
        $logs = [];
        $log = function ($msg) use (&$logs) {
            $logs[] = $msg;
            Log::channel('single')->info('[CleanupSyncedMedia] ' . $msg);
        };

        $log('═══ BẮT ĐẦU CLEANUP ═══');

        // 1. Tìm record đã sync (có link Drive) nhưng vẫn còn path local
        $synced = DB::table('warranty_requests')
            ->select('id', 'image_upload', 'video_upload')
            ->where(function ($q) {
                $q->where('image_upload', 'LIKE', '%drive.google.com%')
                  ->orWhere('video_upload', 'LIKE', '%drive.google.com%');
            })
            ->get();

        $log("Tìm thấy {$synced->count()} record đã có link Drive.");

        // Thu thập đường dẫn local còn sót trong record đã sync
        $localPaths = [];
        foreach ($synced as $record) {
            if (!empty($record->image_upload)) {
                foreach (explode(',', $record->image_upload) as $part) {
                    $part = trim($part);
                    if ($part && !str_contains($part, 'drive.google.com') && !str_starts_with($part, 'https://')) {
                        $localPaths[] = ['id' => $record->id, 'path' => $part, 'type' => 'image'];
                    }
                }
            }
            if (!empty($record->video_upload)) {
                $part = trim($record->video_upload);
                if ($part && !str_contains($part, 'drive.google.com') && !str_starts_with($part, 'https://')) {
                    $localPaths[] = ['id' => $record->id, 'path' => $part, 'type' => 'video'];
                }
            }
        }

        $deleted = 0;
        $skipped = 0;
        $freedBytes = 0;

        foreach ($localPaths as $item) {
            $fullPath = $this->resolveFullPath($item['path']);
            if (!$fullPath || !file_exists($fullPath)) {
                $skipped++;
                continue;
            }
            $size = filesize($fullPath);
            $freedBytes += $size;
            unlink($fullPath);
            $deleted++;
            $log("[ĐÃ XÓA] {$item['path']} ({$this->formatBytes($size)}) — ID #{$item['id']}");
        }

        // 2. Quét file rác trong thư mục photos/ và videos/
        $log('─── QUÉT FILE RÁC KHÔNG CÒN THAM CHIẾU ───');

        $referencedPaths = [];

        DB::table('warranty_requests')
            ->select('id', 'image_upload')
            ->whereNotNull('image_upload')
            ->whereRaw("TRIM(image_upload) <> ''")
            ->where('image_upload', 'NOT LIKE', '%drive.google.com%')
            ->orderBy('id')
            ->chunk(200, function ($rows) use (&$referencedPaths) {
                foreach ($rows as $row) {
                    foreach (explode(',', $row->image_upload) as $p) {
                        $p = trim($p);
                        if ($p && !str_starts_with($p, 'https://')) {
                            $referencedPaths[] = $p;
                        }
                    }
                }
            });

        DB::table('warranty_requests')
            ->select('id', 'video_upload')
            ->whereNotNull('video_upload')
            ->whereRaw("TRIM(video_upload) <> ''")
            ->where('video_upload', 'NOT LIKE', '%drive.google.com%')
            ->whereRaw("video_upload NOT LIKE 'https://%'")
            ->orderBy('id')
            ->chunk(200, function ($rows) use (&$referencedPaths) {
                foreach ($rows as $row) {
                    $referencedPaths[] = trim($row->video_upload);
                }
            });

        // Chuẩn hóa: bỏ storage/ prefix
        $normalizedRefs = collect($referencedPaths)->map(function ($p) {
            return str_starts_with($p, 'storage/') ? substr($p, 8) : $p;
        })->unique()->values()->toArray();

        $dirs = ['photos', 'videos'];
        foreach ($dirs as $dir) {
            $fullDir = storage_path("app/public/{$dir}");
            if (!is_dir($fullDir)) continue;

            $files = File::allFiles($fullDir);
            foreach ($files as $file) {
                $relativePath = "{$dir}/" . str_replace('\\', '/', $file->getRelativePathname());

                if (!in_array($relativePath, $normalizedRefs)) {
                    $size = $file->getSize();
                    $freedBytes += $size;
                    unlink($file->getPathname());
                    $deleted++;
                    $log("[RÁC - ĐÃ XÓA] {$relativePath} ({$this->formatBytes($size)})");
                } else {
                    $log("[GIỮ LẠI] {$relativePath} — vẫn cần đồng bộ");
                }
            }
        }

        $summary = "═══ KẾT QUẢ: Đã xóa {$deleted} file | Bỏ qua: {$skipped} | Giải phóng: {$this->formatBytes($freedBytes)} ═══";
        $log($summary);

        return response()->json([
            'status'  => 'success',
            'deleted' => $deleted,
            'skipped' => $skipped,
            'freed'   => $this->formatBytes($freedBytes),
            'logs'    => $logs,
        ]);
    }

    private function resolveFullPath($path)
    {
        $relativePath = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;

        $storagePath = storage_path("app/public/{$relativePath}");
        if (file_exists($storagePath)) return $storagePath;

        $publicPath = public_path($relativePath);
        if (file_exists($publicPath)) return $publicPath;

        return null;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
