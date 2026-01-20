<?php

namespace App\Console\Commands;

use App\Mail\ReportEmail;
use App\Models\KyThuat\WarrantyReportSnapshot;
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
     * Mapping từ zone key trong user sang branch key trong snapshot
     */
    protected $zoneToBranchMap = [
        'kuchen vinh' => 'kuchen vinh',
        'kuchen hcm' => 'kuchen hcm',
        'kuchen hà nội' => 'kuchen hà nội',
        'hurom vinh' => 'hurom vinh',
        'hurom hcm' => 'hurom hcm',
        'hurom hà nội' => 'hurom hà nội',
        'all' => 'all',
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

            // Tính khoảng thời gian báo cáo MỚI:
            // Weekly: từ 00:00 thứ 2 tuần trước đến 23:59 thứ 2 tuần này
            // Monthly: từ 00:00 ngày 1 tháng trước đến 23:59 ngày cuối tháng trước
            $dateRange = $this->calculateDateRange($type);
            $fromDate = $dateRange['from_date'];
            $toDate = $dateRange['to_date'];
            $reportType = $type === 'weekly' ? 'tuần' : 'tháng';

            $this->info("Đang tạo báo cáo từ {$fromDate->format('d/m/Y H:i')} đến {$toDate->format('d/m/Y H:i')}...");
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

                // Tìm snapshot đã lưu cho zone này
                $branchKey = $this->mapZoneToBranch($zoneKey);
                $snapshot = $this->findSnapshot($type, $fromDate, $toDate, $branchKey);

                if ($snapshot) {
                    $this->info("  → Sử dụng snapshot đã lưu (ID: {$snapshot->id})");
                    $this->logEmailReport('info', sprintf('[%s] Sử dụng snapshot', strtoupper($type)), [
                        'snapshot_id' => $snapshot->id,
                        'snapshot_date' => $snapshot->snapshot_date->format('Y-m-d H:i:s'),
                        'branch' => $branchKey,
                    ]);

                    // Tạo PDF từ snapshot data
                    $result = $service->generateReportPdfFromSnapshot(
                        $fromDate->format('Y-m-d'),
                        $toDate->format('Y-m-d'),
                        $zoneKey,
                        $type,
                        $snapshot
                    );

                    // Đánh dấu snapshot đã gửi
                    $snapshot->markAsSent();
                } else {
                    // Fallback: tính real-time nếu không có snapshot
                    $this->warn("  → Không tìm thấy snapshot, sử dụng dữ liệu real-time");
                    $this->logEmailReport('warning', sprintf('[%s] Không có snapshot, dùng real-time', strtoupper($type)), [
                        'branch' => $branchKey,
                    ]);

                    $result = $service->generateReportPdf(
                        $fromDate->format('Y-m-d'),
                        $toDate->format('Y-m-d'),
                        $zoneKey,
                        $type
                    );
                }

                $this->info("Đã tạo PDF: {$result['file_name']} ({$zoneLabel})");
                $this->logEmailReport('info', sprintf('[%s] Đã tạo PDF', strtoupper($type)), [
                    'zone_key' => $zoneKey,
                    'zone_label' => $zoneLabel,
                    'pdf_file' => $result['file_name'],
                    'used_snapshot' => $snapshot !== null,
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
     * Tính khoảng thời gian báo cáo
     * Weekly: từ 00:00 thứ 2 tuần trước đến 23:59 thứ 2 tuần này
     * Monthly: 30 ngày gần nhất
     */
    protected function calculateDateRange(string $type): array
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        if ($type === 'weekly') {
            // Khi gửi email vào thứ 3, khoảng thời gian báo cáo là:
            // Từ 00:00 thứ 2 tuần trước đến 23:59 thứ 2 tuần này
            
            // Tìm thứ 2 tuần này (hoặc hôm qua nếu hôm nay là thứ 3)
            if ($now->dayOfWeek === Carbon::TUESDAY) {
                // Nếu hôm nay là thứ 3, thì thứ 2 là hôm qua
                $thisMonday = $now->copy()->subDay()->endOfDay();
            } else {
                // Tìm thứ 2 gần nhất (có thể là tuần trước)
                $thisMonday = $now->copy()->previous(Carbon::MONDAY)->endOfDay();
            }
            
            // Thứ 2 tuần trước (00:00)
            $lastMonday = $thisMonday->copy()->subWeek()->startOfDay();
            
            $fromDate = $lastMonday;
            $toDate = $thisMonday;
        } else {
            // Monthly: 30 ngày gần nhất
            $toDate = $now->copy()->endOfDay();
            $fromDate = $toDate->copy()->subDays(30)->startOfDay();
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    /**
     * Tìm snapshot đã lưu cho khoảng thời gian và chi nhánh
     */
    protected function findSnapshot(string $type, Carbon $fromDate, Carbon $toDate, string $branch): ?WarrantyReportSnapshot
    {
        return WarrantyReportSnapshot::where('report_type', $type)
            ->where('branch', $branch)
            ->where('from_date', '>=', $fromDate->copy()->subDay()->format('Y-m-d H:i:s'))
            ->where('to_date', '<=', $toDate->copy()->addDay()->format('Y-m-d H:i:s'))
            ->orderBy('snapshot_date', 'desc')
            ->first();
    }

    /**
     * Chuyển đổi zone key sang branch key
     */
    protected function mapZoneToBranch(string $zoneKey): string
    {
        $zoneKey = strtolower(trim($zoneKey));
        return $this->zoneToBranchMap[$zoneKey] ?? $zoneKey;
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
