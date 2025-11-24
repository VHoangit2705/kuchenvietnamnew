<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;
use App\Jobs\ThongBaoKyThuat;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('clear:oldpdfs')->daily();
        
        // LỊCH GỬI EMAIL BÁO CÁO TỰ ĐỘNG
        // Gửi báo cáo theo tuần: 23:59 thứ 7 (Saturday)
        $schedule->command('report:send-email weekly')
            ->weeklyOn(6, '23:59')
            ->timezone('Asia/Ho_Chi_Minh');
        
        // GỬI BÁO CÁO THEO THÁNG
        // Gửi báo cáo theo tháng: ngày cuối tháng lúc 23:59
        $schedule->command('report:send-email monthly')
            ->dailyAt('23:59')
            ->when(function () {
                // Chạy vào ngày cuối tháng
                $now = Carbon::now('Asia/Ho_Chi_Minh');
                return $now->isLastOfMonth();
            })
            ->timezone('Asia/Ho_Chi_Minh');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}