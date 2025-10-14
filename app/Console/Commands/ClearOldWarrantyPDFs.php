<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearOldWarrantyPDFs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:oldpdfs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $directory = storage_path('app/public/pdfs');
        $files = glob($directory . '/*.pdf');
        $now = time();
        $deleted = 0;

        foreach ($files as $file) {
            if (is_file($file) && $now - filemtime($file) > 86400) {
                unlink($file);
                $deleted++;
            }
        }
        $message = "[" . now() . "] Đã xoá {$deleted} file PDF cũ trong thư mục {$directory}.";
        Log::info($message);
    }
}
