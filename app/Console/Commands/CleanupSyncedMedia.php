<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanupSyncedMedia extends Command
{
    protected $signature = 'cleanup:synced-media {--dry : Chỉ kiểm tra, không xóa}';
    protected $description = 'Xóa ảnh/video local trong public/storage đã được đồng bộ lên Google Drive';

    public function handle()
    {
        $dry = $this->option('dry');

        if ($dry) {
            $this->warn('🔍 CHẾ ĐỘ KIỂM TRA — không xóa file thật.');
        }

        $this->info('');
        $this->info('═══════════════════════════════════════════');
        $this->info('  CLEANUP SYNCED MEDIA — warranty_requests');
        $this->info('═══════════════════════════════════════════');

        // 1. Lấy tất cả record đã có link Drive (đã sync thành công)
        $synced = DB::table('warranty_requests')
            ->select('id', 'image_upload', 'video_upload')
            ->where(function ($q) {
                $q->where('image_upload', 'LIKE', '%drive.google.com%')
                  ->orWhere('video_upload', 'LIKE', '%drive.google.com%');
            })
            ->get();

        $this->info("Tìm thấy {$synced->count()} record đã có link Drive.");
        $this->info('');

        // 2. Thu thập các đường dẫn local có thể còn sót file
        $localPaths = $this->collectLocalPaths($synced);

        if ($localPaths->isEmpty()) {
            $this->info('✅ Không có file local nào cần xóa.');
            return 0;
        }

        $this->info("Tìm thấy {$localPaths->count()} đường dẫn local cần kiểm tra:");
        $this->info('');

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

            if ($dry) {
                $this->line("  [SẼ XÓA] {$item['path']} ({$this->formatBytes($size)}) — ID #{$item['id']}");
            } else {
                unlink($fullPath);
                $this->line("  [ĐÃ XÓA] {$item['path']} ({$this->formatBytes($size)}) — ID #{$item['id']}");
            }
            $deleted++;
        }

        // 3. Quét thêm các file thừa trong thư mục mà không có record nào tham chiếu
        $this->info('');
        $this->info('───────────────────────────────────────────');
        $this->info('  QUÉT FILE RÁC KHÔNG CÒN THAM CHIẾU');
        $this->info('───────────────────────────────────────────');

        $orphanResult = $this->cleanOrphanedFiles($dry);
        $deleted += $orphanResult['deleted'];
        $freedBytes += $orphanResult['freed'];

        $this->info('');
        $this->info('═══════════════════════════════════════════');
        $action = $dry ? 'Sẽ xóa' : 'Đã xóa';
        $this->info("  {$action}: {$deleted} file | Bỏ qua: {$skipped} | Giải phóng: {$this->formatBytes($freedBytes)}");
        $this->info('═══════════════════════════════════════════');

        return 0;
    }

    private function collectLocalPaths($records)
    {
        $paths = collect();

        foreach ($records as $record) {
            // Nếu image_upload chứa cả link Drive VÀ path local (trường hợp mix)
            // thì tách ra và lọc
            if (!empty($record->image_upload)) {
                $parts = explode(',', $record->image_upload);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part && !str_contains($part, 'drive.google.com') && !str_starts_with($part, 'https://')) {
                        $paths->push(['id' => $record->id, 'path' => $part, 'type' => 'image']);
                    }
                }
            }

            if (!empty($record->video_upload)) {
                $part = trim($record->video_upload);
                if ($part && !str_contains($part, 'drive.google.com') && !str_starts_with($part, 'https://')) {
                    $paths->push(['id' => $record->id, 'path' => $part, 'type' => 'video']);
                }
            }
        }

        return $paths;
    }

    private function cleanOrphanedFiles($dry)
    {
        $deleted = 0;
        $freed = 0;

        // Lấy tất cả local paths còn tham chiếu trong DB (chưa sync xong)
        $referencedPaths = [];

        // image_upload chưa sync
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

        // video_upload chưa sync
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

        // Normalize paths: bỏ storage/ prefix để so sánh
        $normalizedRefs = collect($referencedPaths)->map(function ($p) {
            return str_starts_with($p, 'storage/') ? substr($p, 8) : $p;
        })->unique()->values()->toArray();

        // Quét directories
        $dirs = ['photos', 'videos'];
        foreach ($dirs as $dir) {
            $fullDir = storage_path("app/public/{$dir}");
            if (!is_dir($fullDir)) continue;

            $files = File::allFiles($fullDir);
            foreach ($files as $file) {
                $relativePath = "{$dir}/" . $file->getRelativePathname();
                $relativePath = str_replace('\\', '/', $relativePath);

                if (!in_array($relativePath, $normalizedRefs)) {
                    $size = $file->getSize();
                    $freed += $size;

                    if ($dry) {
                        $this->line("  [RÁC - SẼ XÓA] {$relativePath} ({$this->formatBytes($size)})");
                    } else {
                        unlink($file->getPathname());
                        $this->line("  [RÁC - ĐÃ XÓA] {$relativePath} ({$this->formatBytes($size)})");
                    }
                    $deleted++;
                } else {
                    $this->line("  [GIỮ LẠI] {$relativePath} — vẫn còn cần đồng bộ");
                }
            }
        }

        return ['deleted' => $deleted, 'freed' => $freed];
    }

    private function resolveFullPath($path)
    {
        // Chuẩn hóa: bỏ tiền tố storage/ nếu có
        $relativePath = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;

        // Trường hợp 1: file trong storage/app/public/ (photos/, videos/)
        $storagePath = storage_path("app/public/{$relativePath}");
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        // Trường hợp 2: file trong public/ (uploads/photos/, uploads/videos/)
        $publicPath = public_path($relativePath);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

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
