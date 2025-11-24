<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Jobs\ThongBaoKyThuat;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('clear:oldpdfs')->daily();
        
        // LỊCH GỬI EMAIL BÁO CÁO TỰ ĐỘNG
        // Gửi báo cáo theo tuần: 15:00 thứ 7 (Saturday)
        $schedule->command('report:send-email weekly')
            ->weeklyOn(6, '15:00')
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[WEEKLY] Bắt đầu chạy command report:send-email weekly');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[WEEKLY] Hoàn tất command report:send-email weekly');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[WEEKLY] Command report:send-email weekly thất bại');
            });
        
        // GỬI BÁO CÁO THEO THÁNG
        // Gửi báo cáo theo tháng: ngày cuối tháng lúc 23:59
        $schedule->command('report:send-email monthly')
            ->dailyAt('23:59')
            ->when(function () {
                // Chạy vào ngày cuối tháng
                $now = Carbon::now('Asia/Ho_Chi_Minh');
                return $now->isLastOfMonth();
            })
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[MONTHLY] Bắt đầu chạy command report:send-email monthly');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[MONTHLY] Hoàn tất command report:send-email monthly');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[MONTHLY] Command report:send-email monthly thất bại');
            });
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}