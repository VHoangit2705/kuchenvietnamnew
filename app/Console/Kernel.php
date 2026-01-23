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
        
        // ============================================================
        // LUỒNG BÁO CÁO TUẦN - KHÓA CỨNG DỮ LIỆU
        // ============================================================
        // Bước 1: Lưu snapshot dữ liệu vào 23:59 thứ 2 (Monday)
        // Khoảng thời gian: 00:00 thứ 2 tuần trước → 23:59 thứ 2 tuần này
        $schedule->command('report:save-snapshot weekly')
            ->weeklyOn(1, '23:59') // Monday 23:59
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[SNAPSHOT] Bắt đầu lưu snapshot dữ liệu báo cáo tuần');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[SNAPSHOT] Hoàn tất lưu snapshot dữ liệu báo cáo tuần');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[SNAPSHOT] Lỗi khi lưu snapshot dữ liệu báo cáo tuần');
            });

        // Bước 2: Gửi email báo cáo tuần vào 08:00 thứ 3 (Tuesday)
        // Sử dụng snapshot đã lưu từ thứ 2
        $schedule->command('report:send-email weekly')
            ->weeklyOn(2, '08:00') // Tuesday 08:00
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[WEEKLY] Bắt đầu gửi email báo cáo tuần (sử dụng snapshot)');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[WEEKLY] Hoàn tất gửi email báo cáo tuần');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[WEEKLY] Lỗi khi gửi email báo cáo tuần');
            });
        
        // ============================================================
        // LUỒNG BÁO CÁO THÁNG - KHÓA CỨNG DỮ LIỆU
        // ============================================================
        // Bước 1: Lưu snapshot dữ liệu vào 23:59 ngày 29 hàng tháng
        $schedule->command('report:save-snapshot monthly')
            ->dailyAt('23:59')
            ->when(function () {
                $now = Carbon::now('Asia/Ho_Chi_Minh');
                return $now->day === 29;
            })
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[SNAPSHOT] Bắt đầu lưu snapshot dữ liệu báo cáo tháng');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[SNAPSHOT] Hoàn tất lưu snapshot dữ liệu báo cáo tháng');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[SNAPSHOT] Lỗi khi lưu snapshot dữ liệu báo cáo tháng');
            });

        // Bước 2: Gửi email báo cáo tháng vào 08:00 ngày 30 hàng tháng
        $schedule->command('report:send-email monthly')
            ->dailyAt('08:00')
            ->when(function () {
                $now = Carbon::now('Asia/Ho_Chi_Minh');
                return $now->day === 30;
            })
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[MONTHLY] Bắt đầu gửi email báo cáo tháng (sử dụng snapshot)');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[MONTHLY] Hoàn tất gửi email báo cáo tháng');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[MONTHLY] Lỗi khi gửi email báo cáo tháng');
            });

        // ============================================================
        // LƯU LỊCH SỬ TỈ LỆ QUÁ HẠN (GIỮ NGUYÊN)
        // ============================================================
        // Lưu lịch sử tỉ lệ quá hạn theo tuần: sau khi gửi email báo cáo tuần
        $schedule->command('report:save-overdue-history weekly')
            ->weeklyOn(2, '08:30') // Tuesday 08:30 (sau khi gửi email)
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[OVERDUE_HISTORY] Bắt đầu lưu lịch sử tỉ lệ quá hạn tuần');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[OVERDUE_HISTORY] Hoàn tất lưu lịch sử tỉ lệ quá hạn tuần');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[OVERDUE_HISTORY] Lỗi khi lưu lịch sử tỉ lệ quá hạn tuần');
            });

        // Lưu lịch sử tỉ lệ quá hạn theo tháng: sau khi gửi email báo cáo tháng
        $schedule->command('report:save-overdue-history monthly')
            ->dailyAt('08:30')
            ->when(function () {
                $now = Carbon::now('Asia/Ho_Chi_Minh');
                return $now->day === 30;
            })
            ->timezone('Asia/Ho_Chi_Minh')
            ->before(function () {
                Log::channel('email_report')->info('[OVERDUE_HISTORY] Bắt đầu lưu lịch sử tỉ lệ quá hạn tháng');
            })
            ->onSuccess(function () {
                Log::channel('email_report')->info('[OVERDUE_HISTORY] Hoàn tất lưu lịch sử tỉ lệ quá hạn tháng');
            })
            ->onFailure(function () {
                Log::channel('email_report')->error('[OVERDUE_HISTORY] Lỗi khi lưu lịch sử tỉ lệ quá hạn tháng');
            });
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
