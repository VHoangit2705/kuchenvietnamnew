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
        
        // Xóa file báo cáo cũ (cũ hơn 60 ngày) - chạy hàng tuần vào chủ nhật lúc 2:00
        $schedule->command('clear:old-reports --days=60')
            ->weeklyOn(0, '02:00')
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::info('[CLEANUP] Bắt đầu xóa file báo cáo cũ');
            })
            ->onSuccess(function () {
                Log::info('[CLEANUP] Hoàn tất xóa file báo cáo cũ');
            })
            ->onFailure(function () {
                Log::error('[CLEANUP] Lỗi khi xóa file báo cáo cũ');
            });
        
        // LỊCH GỬI EMAIL BÁO CÁO TỰ ĐỘNG
        // Gửi báo cáo tổng hợp: 08:00 thứ 3 hàng tuần (Tuesday)
        $schedule->command('report:send-email weekly')
            ->weeklyOn(2, '08:00')
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[WEEKLY_SUMMARY] Bắt đầu chạy command report:send-email weekly (tổng hợp thứ 3)');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[WEEKLY_SUMMARY] Hoàn tất command report:send-email weekly (tổng hợp thứ 3)');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[WEEKLY_SUMMARY] Command report:send-email weekly (tổng hợp thứ 3) thất bại');
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

        // LƯU LỊCH SỬ TỈ LỆ QUÁ HẠN
        // Lưu lịch sử tỉ lệ quá hạn theo tuần: sau khi gửi email báo cáo tuần
        $schedule->command('report:save-overdue-history weekly')
            ->weeklyOn(6, '15:10') // Chạy sau 5 phút khi gửi email báo cáo tuần (15:00)
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[OVERDUE_HISTORY] Bắt đầu chạy command report:save-overdue-history weekly');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[OVERDUE_HISTORY] Hoàn tất command report:save-overdue-history weekly');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[OVERDUE_HISTORY] Command report:save-overdue-history weekly thất bại');
            });

        // Lưu lịch sử tỉ lệ quá hạn theo tháng: sau khi gửi email báo cáo tháng
        $schedule->command('report:save-overdue-history monthly')
            ->dailyAt('23:59')
            ->when(function () {
                // Chạy vào ngày cuối tháng, sau khi gửi email báo cáo
                $now = Carbon::now('Asia/Ho_Chi_Minh');
                return $now->isLastOfMonth();
            })
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[OVERDUE_HISTORY] Bắt đầu chạy command report:save-overdue-history monthly');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[OVERDUE_HISTORY] Hoàn tất command report:save-overdue-history monthly');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[OVERDUE_HISTORY] Command report:save-overdue-history monthly thất bại');
            });
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
