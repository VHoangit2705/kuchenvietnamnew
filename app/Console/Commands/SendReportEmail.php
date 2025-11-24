<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReportEmail;
use App\Services\SendReport\ReportEmailService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendReportEmail extends Command
{
    /**
     * Đăng kí command để gửi email báo cáo tự động
     */
    protected $signature = 'report:send-email {type=weekly : Type of report (weekly or monthly)}';

    /**
     * Mô tả nhiệm vụ của command
     */
    protected $description = 'Gửi email báo cáo tự động theo tuần hoặc tháng';

    /**
     * CẤU HÌNH EMAIL - DANH SÁCH NGƯỜI NHẬN
     * Thêm email của các trưởng phòng vào đây để nhận báo cáo tự động
     * protected $recipientEmails = [
     *     'truongphong1@gmail.com',
     *     'truongphong2@gmail.com',
     *     'truongphong3@gmail.com',
     * ];
     */
    protected $recipientEmails = [
        'nghia520414@gmail.com',
        // 'lcnghia.20it12@vku.udn.vn'
        // Thêm email khác tại đây...
    ];

    /**
     * CẤU HÌNH EMAIL - ĐỊA CHỈ GỬI ĐI
     * Email công ty dùng để gửi báo cáo
     * Lưu ý: Email này phải được cấu hình trong file .env (MAIL_FROM_ADDRESS)
     * Nếu không, sẽ dùng email mặc định từ config('mail.from.address')
     */
    protected $fromEmail = 'nghia520414@gmail.com'; // Email công ty dùng để gửi báo cáo

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        
        try {
            // Calculate date range based on type
            if ($type === 'weekly') {
                // Weekly report: from last Saturday to this Saturday
                $toDate = Carbon::now('Asia/Ho_Chi_Minh')->endOfDay();
                $fromDate = $toDate->copy()->subWeek()->startOfDay();
                $reportType = 'tuần';
            } else {
                // Monthly report: last 30 days
                $toDate = Carbon::now('Asia/Ho_Chi_Minh')->endOfDay();
                $fromDate = $toDate->copy()->subDays(30)->startOfDay();
                $reportType = 'tháng';
            }

            $this->info("Đang tạo báo cáo từ {$fromDate->format('d/m/Y')} đến {$toDate->format('d/m/Y')}...");

            // Generate report PDF
            $service = new ReportEmailService();
            $result = $service->generateReportPdf(
                $fromDate->format('Y-m-d'),
                $toDate->format('Y-m-d'),
                'all' // All branches
            );

            $this->info("Đã tạo PDF: {$result['file_name']}");
            $this->info("Đường dẫn: {$result['full_path']}");

            // GỬI EMAIL ĐẾN TỪNG NGƯỜI NHẬN
            // Lặp qua danh sách email trong $recipientEmails và gửi báo cáo
            $fromDateFormatted = $fromDate->format('d/m/Y');
            $toDateFormatted = $toDate->format('d/m/Y');

            foreach ($this->recipientEmails as $email) {
                try {
                    Mail::to($email)->send(
                        new ReportEmail($result['full_path'], $fromDateFormatted, $toDateFormatted, $reportType)
                    );
                    $this->info("Đã gửi email đến: {$email}");
                    Log::info("Đã gửi báo cáo {$reportType} đến {$email}", [
                        'from_date' => $fromDateFormatted,
                        'to_date' => $toDateFormatted,
                        'pdf_file' => $result['file_name']
                    ]);
                } catch (\Exception $e) {
                    $this->error("Lỗi khi gửi email đến {$email}: " . $e->getMessage());
                    Log::error("Lỗi khi gửi báo cáo đến {$email}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->info("Hoàn tất gửi email báo cáo {$reportType}!");

        } catch (\Exception $e) {
            $this->error("Lỗi: " . $e->getMessage());
            Log::error("Lỗi khi tạo và gửi báo cáo", [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }
}

