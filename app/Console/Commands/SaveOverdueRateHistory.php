<?php

namespace App\Console\Commands;

use App\Models\KyThuat\WarrantyRequest;
use App\Models\KyThuat\WarrantyOverdueRateHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaveOverdueRateHistory extends Command
{
    /**
     * Đăng ký command để lưu lịch sử tỉ lệ quá hạn
     */
    protected $signature = 'report:save-overdue-history {type=weekly : Type of report (weekly or monthly)}';

    /**
     * Mô tả nhiệm vụ của command
     */
    protected $description = 'Lưu lịch sử tỉ lệ quá hạn ca bảo hành theo tuần hoặc tháng';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        try {
            $this->info("Bắt đầu lưu lịch sử tỉ lệ quá hạn ({$type})...");
            Log::channel('email_report')->info("[OVERDUE_HISTORY] Bắt đầu lưu lịch sử tỉ lệ quá hạn", [
                'type' => $type,
            ]);

            // Tính toán khoảng thời gian (giống với logic trong SendReportEmail)
            if ($type === 'weekly') {
                // Báo cáo tuần: từ thứ 7 tuần trước đến thứ 7 tuần này (7 ngày gần nhất)
                $toDate = Carbon::now('Asia/Ho_Chi_Minh')->endOfDay();
                $fromDate = $toDate->copy()->subWeek()->startOfDay();
            } else {
                // Báo cáo tháng: 30 ngày gần nhất
                $toDate = Carbon::now('Asia/Ho_Chi_Minh')->endOfDay();
                $fromDate = $toDate->copy()->subDays(30)->startOfDay();
            }

            $reportDate = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();

            $this->info("Khoảng thời gian: {$fromDate->format('d/m/Y')} đến {$toDate->format('d/m/Y')}");

            // Chỉ lưu theo từng kỹ thuật viên
            $this->saveStaffStats($reportDate, $type, $fromDate, $toDate);

            $this->info("Hoàn tất lưu lịch sử tỉ lệ quá hạn ({$type})!");
            Log::channel('email_report')->info("[OVERDUE_HISTORY] Hoàn tất lưu lịch sử tỉ lệ quá hạn", [
                'type' => $type,
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error("Lỗi: " . $e->getMessage());
            Log::channel('email_report')->error("[OVERDUE_HISTORY] Lỗi khi lưu lịch sử tỉ lệ quá hạn", [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Lưu thống kê theo từng kỹ thuật viên
     */
    protected function saveStaffStats($reportDate, $type, $fromDate, $toDate)
    {
        $staffList = WarrantyRequest::whereBetween('received_date', [$fromDate, $toDate])
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '')
            ->select('staff_received', 'branch')
            ->distinct()
            ->get();

        foreach ($staffList as $staff) {
            $stats = $this->calculateStats($staff->branch, $staff->staff_received, $fromDate, $toDate);

            WarrantyOverdueRateHistory::updateOrCreate(
                [
                    'report_type' => $type,
                    'from_date' => $fromDate->toDateString(),
                    'to_date' => $toDate->toDateString(),
                    'branch' => $staff->branch,
                    'staff_received' => $staff->staff_received,
                ],
                [
                    'report_date' => $reportDate,
                    'tong_tiep_nhan' => $stats['tong_tiep_nhan'],
                    'so_ca_qua_han' => $stats['so_ca_qua_han'],
                    'ti_le_qua_han' => $stats['ti_le_qua_han'],
                    'dang_sua_chua' => $stats['dang_sua_chua'],
                    'cho_khach_hang_phan_hoi' => $stats['cho_khach_hang_phan_hoi'],
                    'da_hoan_tat' => $stats['da_hoan_tat'],
                ]
            );
        }

        $this->info("Đã lưu thống kê cho {$staffList->count()} kỹ thuật viên");
    }

    /**
     * Tính toán thống kê dựa trên điều kiện
     */
    protected function calculateStats($branch = null, $staffReceived = null, $fromDate, $toDate)
    {
        $query = WarrantyRequest::query()
            ->whereBetween('received_date', [$fromDate, $toDate])
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '');

        if ($branch) {
            $query->where('branch', 'LIKE', '%' . $branch . '%');
        }

        if ($staffReceived) {
            $query->where('staff_received', 'LIKE', '%' . $staffReceived . '%');
        }

        $stats = $query
            ->select(
                DB::raw('COUNT(*) as tong_tiep_nhan'),
                DB::raw('SUM(CASE WHEN status = "Đang sửa chữa" THEN 1 ELSE 0 END) as dang_sua_chua'),
                DB::raw('SUM(CASE WHEN status = "Chờ KH phản hồi" THEN 1 ELSE 0 END) as cho_khach_hang_phan_hoi'),
                DB::raw('SUM(CASE WHEN status = "Đã hoàn tất" THEN 1 ELSE 0 END) as da_hoan_tat'),
                DB::raw('SUM(
                    CASE 
                        WHEN status != "Đã hoàn tất" 
                            AND status != "Chờ KH phản hồi" 
                            AND return_date IS NOT NULL 
                            AND DATE(return_date) < CURDATE()
                        THEN 1 
                        ELSE 0 
                    END
                ) as so_ca_qua_han')
            )
            ->first();

        $tongTiepNhan = (int)($stats->tong_tiep_nhan ?? 0);
        $soCaQuaHan = (int)($stats->so_ca_qua_han ?? 0);
        $tiLeQuaHan = $tongTiepNhan > 0 
            ? round(($soCaQuaHan / $tongTiepNhan) * 100, 2) 
            : 0;

        return [
            'tong_tiep_nhan' => $tongTiepNhan,
            'so_ca_qua_han' => $soCaQuaHan,
            'ti_le_qua_han' => $tiLeQuaHan,
            'dang_sua_chua' => (int)($stats->dang_sua_chua ?? 0),
            'cho_khach_hang_phan_hoi' => (int)($stats->cho_khach_hang_phan_hoi ?? 0),
            'da_hoan_tat' => (int)($stats->da_hoan_tat ?? 0),
        ];
    }
}

