<?php

namespace App\Console\Commands;

use App\Mail\ReportEmail;
use App\Services\SendReport\ReportEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
     * Các chức danh sẽ nhận báo cáo tự động (so khớp không phân biệt hoa thường).
     */
    protected $targetPositions = [
        'quản trị viên',
        'admin',
        'trưởng phòng',
        'phó phòng',
    ];

    /**
     * Các chức danh luôn nhận báo cáo tổng hợp tất cả chi nhánh.
     */
    protected $globalPositions = [
        'quản trị viên',
        'admin',
    ];

    /**
     * Theo dõi những email đã được gửi trong phiên chạy hiện tại.
     *
     * @var array<string, bool>
     */
    protected array $sentEmails = [];

    /**
     * Tên hiển thị cho từng zone (chi nhánh).
     */
    protected $branchLabels = [
        'kchanoi' => 'KUCHEN HÀ NỘI',
        'kcvinh' => 'KUCHEN VINH',
        'kchcm' => 'KUCHEN HCM',
        'hrhanoi' => 'HUROM HÀ NỘI',
        'hrvinh' => 'HUROM VINH',
        'hrhcm' => 'HUROM HCM',
    ];

    /**
     * Ghi log vào file email_report.log
     */
    protected function logEmailReport(string $level, string $message, array $context = []): void
    {
        $logger = Log::channel('email_report');

        if (!method_exists($logger, $level)) {
            $level = 'info';
        }

        $logger->{$level}($message, $context);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        try {
            $this->logEmailReport('info', sprintf('[%s] Khởi động command report:send-email', strtoupper($type)), [
                'type' => $type,
            ]);
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
            $this->logEmailReport('info', sprintf('[%s] Khởi tạo báo cáo', strtoupper($type)), [
                'from_date' => $fromDate->format('Y-m-d H:i:s'),
                'to_date' => $toDate->format('Y-m-d H:i:s'),
                'report_type' => $reportType,
            ]);

            $recipientsByZone = $this->prepareRecipientGroups();
            $this->logEmailReport('info', sprintf('[%s] Nhóm người nhận', strtoupper($type)), [
                'total_groups' => $recipientsByZone->count(),
                'groups' => $recipientsByZone->keys()->values()->all(),
            ]);

            if ($recipientsByZone->isEmpty()) {
                $this->warn('Không tìm thấy người nhận hợp lệ để gửi báo cáo.');
                $this->logEmailReport('warning', sprintf('[%s] Không có người nhận hợp lệ', strtoupper($type)));
                return 0;
            }

            $service = new ReportEmailService();
            $fromDateFormatted = $fromDate->format('d/m/Y');
            $toDateFormatted = $toDate->format('d/m/Y');
            $weekNumber = null;
            $monthNumber = $toDate->month;
            $periodTitle = 'Tháng ' . $monthNumber;

            if ($type === 'weekly') {
                $weekNumber = $toDate->weekOfMonth;
                $periodTitle = 'Tuần thứ ' . $weekNumber . ' trong tháng ' . $monthNumber;
            }

            foreach ($recipientsByZone as $zoneKey => $users) {
                $zoneLabel = $this->formatZoneLabel($zoneKey);
                $this->info("Đang tạo báo cáo cho {$zoneLabel}...");
                $this->logEmailReport('info', sprintf('[%s] Bắt đầu tạo PDF', strtoupper($type)), [
                    'zone_key' => $zoneKey,
                    'zone_label' => $zoneLabel,
                ]);

                $result = $service->generateReportPdf(
                    $fromDate->format('Y-m-d'),
                    $toDate->format('Y-m-d'),
                    $zoneKey
                );

                $this->info("Đã tạo PDF: {$result['file_name']} ({$zoneLabel})");
                $this->logEmailReport('info', sprintf('[%s] Đã tạo PDF', strtoupper($type)), [
                    'zone_key' => $zoneKey,
                    'zone_label' => $zoneLabel,
                    'pdf_file' => $result['file_name'],
                ]);
                $this->deliverReportToRecipients(
                    $users,
                    $result,
                    $fromDateFormatted,
                    $toDateFormatted,
                    $reportType,
                    $zoneKey,
                    $zoneLabel,
                    $weekNumber,
                    $monthNumber,
                    $periodTitle
                );
            }

            $this->info("Hoàn tất gửi email báo cáo {$reportType}!");
            $this->logEmailReport('info', sprintf('[%s] Hoàn tất command report:send-email', strtoupper($type)), [
                'report_type' => $reportType,
            ]);
        } catch (\Exception $e) {
            $this->error("Lỗi: " . $e->getMessage());
            Log::error("Lỗi khi tạo và gửi báo cáo", [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->logEmailReport('error', sprintf('[%s] Lỗi khi gửi báo cáo', strtoupper($type)), [
                'error' => $e->getMessage(),
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Lấy danh sách người nhận, gom nhóm theo zone/chi nhánh.
     */
    protected function prepareRecipientGroups(): Collection
    {
        $recipients = ReportEmail::reportRecipients($this->targetPositions)->toBase();

        $globalRecipients = $recipients->filter(function ($user) {
            return $this->shouldReceiveAllZones($user);
        });

        $zoneRecipients = $recipients->reject(function ($user) {
            return $this->shouldReceiveAllZones($user);
        });

        $grouped = $zoneRecipients
            ->groupBy(function ($user) {
                return $this->normalizeZoneKey($user->zone ?? '');
            });

        $allRecipients = $grouped->get('all', collect());

        if ($globalRecipients->isNotEmpty()) {
            $allRecipients = $allRecipients->merge($globalRecipients);
        }

        if ($allRecipients->isNotEmpty()) {
            $grouped->put('all', $allRecipients);
        }

        return $grouped->filter(function ($users) {
            return $users->isNotEmpty();
        });
    }

    /**
     * Chuẩn hoá zone về key xử lý.
     */
    protected function normalizeZoneKey(?string $zone): string
    {
        $zone = trim((string) $zone);

        if ($zone === '') {
            return 'all';
        }

        return strtolower($zone);
    }

    /**
     * Lấy tên hiển thị cho zone.
     */
    protected function formatZoneLabel(string $zoneKey): string
    {
        if ($zoneKey === 'all') {
            return 'Tất cả chi nhánh';
        }

        return $this->branchLabels[$zoneKey] ?? strtoupper($zoneKey);
    }

    /**
     * Kiểm tra người dùng có cần nhận báo cáo tất cả chi nhánh hay không.
     */
    protected function shouldReceiveAllZones($user): bool
    {
        $position = mb_strtolower(trim((string) ($user->position ?? '')), 'UTF-8');
        $normalizedTargets = array_map(function ($value) {
            return mb_strtolower(trim($value), 'UTF-8');
        }, $this->globalPositions);

        return in_array($position, $normalizedTargets, true);
    }

    /**
     * Gửi email báo cáo cho từng nhóm người nhận.
     */
    protected function deliverReportToRecipients(
        Collection $users,
        array $pdfResult,
        string $fromDateFormatted,
        string $toDateFormatted,
        string $reportType,
        string $zoneKey,
        string $zoneLabel,
        ?int $weekNumber,
        ?int $monthNumber,
        ?string $periodTitle
    ): void {
        foreach ($users as $user) {
            $email = $user->email ?? null;

            if (empty($email)) {
                continue;
            }

            if ($this->hasAlreadySentTo($email)) {
                $this->warn("Bỏ qua email trùng lặp: {$email}");
                $this->logEmailReport('warning', '[DUPLICATE] Bỏ qua email trùng lặp', [
                    'email' => $email,
                    'zone_label' => $zoneLabel,
                ]);
                continue;
            }

            try {
                $this->logEmailReport('info', '[SEND] Chuẩn bị gửi báo cáo', [
                    'email' => $email,
                    'zone_key' => $zoneKey,
                    'zone_label' => $zoneLabel,
                    'report_type' => $reportType,
                    'period' => $periodTitle,
                ]);
                Mail::to($email)->send(
                    new ReportEmail(
                        $pdfResult['full_path'],
                        $fromDateFormatted,
                        $toDateFormatted,
                        $reportType,
                        $zoneKey,
                        $zoneLabel,
                        $weekNumber,
                        $monthNumber,
                        $periodTitle
                    )
                );

                $this->info("Đã gửi email đến: {$email} ({$zoneLabel})");
                Log::info("Đã gửi báo cáo {$reportType} đến {$email}", [
                    'zone' => $zoneKey,
                    'zone_label' => $zoneLabel,
                    'from_date' => $fromDateFormatted,
                    'to_date' => $toDateFormatted,
                    'pdf_file' => $pdfResult['file_name'],
                    'user_id' => $user->id ?? null,
                    'position' => $user->position ?? null,
                ]);
                $this->logEmailReport('info', '[SEND] Đã gửi báo cáo thành công', [
                    'email' => $email,
                    'zone_label' => $zoneLabel,
                    'pdf_file' => $pdfResult['file_name'],
                ]);
            } catch (\Exception $e) {
                $this->error("Lỗi khi gửi email đến {$email} ({$zoneLabel}): " . $e->getMessage());
                Log::error("Lỗi khi gửi báo cáo đến {$email}", [
                    'zone' => $zoneKey,
                    'zone_label' => $zoneLabel,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $user->id ?? null,
                ]);
                $this->logEmailReport('error', '[SEND] Gửi báo cáo thất bại', [
                    'email' => $email,
                    'zone_label' => $zoneLabel,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Kiểm tra một email đã được gửi trước đó trong phiên chạy chưa.
     */
    protected function hasAlreadySentTo(string $email): bool
    {
        $normalized = mb_strtolower(trim($email), 'UTF-8');

        if (isset($this->sentEmails[$normalized])) {
            return true;
        }

        $this->sentEmails[$normalized] = true;

        return false;
    }
}
