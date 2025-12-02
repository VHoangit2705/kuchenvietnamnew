<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class ClearOldReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:old-reports {--days=60 : Số ngày để giữ lại file (mặc định 60 ngày)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa các file báo cáo cũ trong storage/app/reports/ (mặc định: > 60 ngày)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $directory = storage_path('app/reports');
        
        if (!is_dir($directory)) {
            $this->info("Thư mục {$directory} không tồn tại.");
            return 0;
        }

        $files = glob($directory . '/*.pdf');
        $cutoffDate = Carbon::now()->subDays($days);
        $deleted = 0;
        $totalSize = 0;
        $freedSize = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $fileModifiedTime = Carbon::createFromTimestamp(filemtime($file));
                $fileSize = filesize($file);
                $totalSize += $fileSize;

                if ($fileModifiedTime->lt($cutoffDate)) {
                    $freedSize += $fileSize;
                    if (unlink($file)) {
                        $deleted++;
                        $this->line("Đã xóa: " . basename($file) . " (tạo ngày: {$fileModifiedTime->format('Y-m-d H:i:s')})");
                    } else {
                        $this->error("Không thể xóa: " . basename($file));
                    }
                }
            }
        }

        $message = sprintf(
            "[%s] Đã xóa %d file báo cáo cũ (cũ hơn %d ngày) trong thư mục %s. Giải phóng: %s. Tổng dung lượng hiện tại: %s.",
            now()->format('Y-m-d H:i:s'),
            $deleted,
            $days,
            $directory,
            $this->formatBytes($freedSize),
            $this->formatBytes($totalSize - $freedSize)
        );

        Log::info($message);
        $this->info($message);

        return 0;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

