<?php

namespace App\Console\Commands;

use App\Models\KyThuat\WarrantyRequest;
use App\Models\KyThuat\WarrantyReportSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaveReportSnapshot extends Command
{
    /**
     * Đăng ký command để lưu snapshot dữ liệu báo cáo
     */
    protected $signature = 'report:save-snapshot {type=weekly : Type of report (weekly or monthly)}';

    /**
     * Mô tả nhiệm vụ của command
     */
    protected $description = 'Lưu snapshot dữ liệu báo cáo để khóa cứng số liệu tại thời điểm tính toán';

    /**
     * Danh sách chi nhánh cần tạo snapshot
     * Mỗi chi nhánh có view tương ứng (1 = kuchen, 3 = hurom)
     */
    protected $branches = [
        'kuchen vinh' => 1,
        'kuchen hcm' => 1,
        'kuchen hà nội' => 1,
        'hurom vinh' => 3,
        'hurom hcm' => 3,
        'hurom hà nội' => 3,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        try {
            $this->logSnapshot('info', sprintf('[%s] Bắt đầu lưu snapshot dữ liệu báo cáo', strtoupper($type)), [
                'type' => $type,
            ]);

            // Tính toán khoảng thời gian
            $dateRange = $this->calculateDateRange($type);
            $fromDate = $dateRange['from_date'];
            $toDate = $dateRange['to_date'];
            $snapshotDate = Carbon::now('Asia/Ho_Chi_Minh');

            $this->info("Khoảng thời gian báo cáo: {$fromDate->format('d/m/Y H:i')} đến {$toDate->format('d/m/Y H:i')}");
            $this->logSnapshot('info', sprintf('[%s] Khoảng thời gian báo cáo', strtoupper($type)), [
                'from_date' => $fromDate->format('Y-m-d H:i:s'),
                'to_date' => $toDate->format('Y-m-d H:i:s'),
            ]);

            // Tạo snapshot cho từng chi nhánh
            foreach ($this->branches as $branch => $view) {
                $this->createBranchSnapshot($type, $fromDate, $toDate, $snapshotDate, $branch, $view);
            }

            // Tạo snapshot tổng hợp tất cả chi nhánh KUCHEN (view = 1)
            $this->createBranchSnapshot($type, $fromDate, $toDate, $snapshotDate, 'all', 1);

            // Tạo snapshot tổng hợp tất cả chi nhánh HUROM (view = 3) - nếu cần
            // $this->createBranchSnapshot($type, $fromDate, $toDate, $snapshotDate, 'all_hurom', 3);

            $this->info("Hoàn tất lưu snapshot dữ liệu báo cáo ({$type})!");
            $this->logSnapshot('info', sprintf('[%s] Hoàn tất lưu snapshot', strtoupper($type)), [
                'total_branches' => count($this->branches) + 1,
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error("Lỗi: " . $e->getMessage());
            $this->logSnapshot('error', sprintf('[%s] Lỗi khi lưu snapshot', strtoupper($type)), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Tính toán khoảng thời gian báo cáo
     * Weekly: 00:00 thứ 2 tuần hiện tại → 23:59 thứ 2 tuần này (chạy vào 23:59 thứ 2)
     * Monthly: 00:00 ngày 1 tháng này → 23:59 ngày 30 tháng này
     */
    protected function calculateDateRange(string $type): array
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        if ($type === 'weekly') {
            // Khi chạy vào 23:59 thứ 2, tính khoảng thời gian:
            // Từ 00:00 thứ 2 tuần trước đến 23:59 thứ 2 tuần này
            
            // Tìm thứ 2 tuần trước (00:00)
            $fromDate = $now->copy()->previous(Carbon::MONDAY)->subWeek()->startOfDay();
            
            // Thứ 2 tuần này (23:59) - là ngày hôm nay nếu chạy vào thứ 2
            $toDate = $now->copy()->previous(Carbon::MONDAY)->endOfDay();
            
            // Nếu hôm nay là thứ 2, thì previous(MONDAY) trả về thứ 2 tuần trước
            // Cần điều chỉnh
            if ($now->dayOfWeek === Carbon::MONDAY) {
                $fromDate = $now->copy()->subWeek()->startOfDay();
                $toDate = $now->copy()->endOfDay();
            }
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
     * Tạo snapshot cho một chi nhánh
     */
    protected function createBranchSnapshot(
        string $type,
        Carbon $fromDate,
        Carbon $toDate,
        Carbon $snapshotDate,
        string $branch,
        int $view = 1
    ): void {
        $branchLabel = $branch === 'all' ? 'Tất cả chi nhánh KUCHEN' : strtoupper($branch);
        $this->info("Đang tạo snapshot cho: " . $branchLabel);

        // Lấy dữ liệu warranty_requests
        $warrantyData = $this->getWarrantyData($fromDate, $toDate, $branch, $view);
        
        // Lấy thống kê quy trình làm việc
        $workProcessData = $this->getWorkProcessData($fromDate, $toDate, $branch, $view);
        
        // Tạo summary
        $summaryData = $this->createSummary($warrantyData, $workProcessData);

        // Lưu snapshot
        WarrantyReportSnapshot::createOrUpdateSnapshot([
            'report_type' => $type,
            'from_date' => $fromDate->format('Y-m-d H:i:s'),
            'to_date' => $toDate->format('Y-m-d H:i:s'),
            'snapshot_date' => $snapshotDate->format('Y-m-d H:i:s'),
            'branch' => $branch,
            'warranty_data' => json_encode($warrantyData, JSON_UNESCAPED_UNICODE),
            'work_process_data' => json_encode($workProcessData, JSON_UNESCAPED_UNICODE),
            'summary_data' => $summaryData,
        ]);

        $this->info("  → Đã lưu: {$summaryData['total_records']} ca hoàn tất, {$summaryData['total_tiep_nhan']} tổng tiếp nhận, {$summaryData['total_staff']} kỹ thuật viên");
        $this->logSnapshot('info', sprintf('[%s] Đã lưu snapshot', strtoupper($type)), [
            'branch' => $branch,
            'view' => $view,
            'total_records' => $summaryData['total_records'],
            'total_tiep_nhan' => $summaryData['total_tiep_nhan'],
            'total_staff' => $summaryData['total_staff'],
        ]);
    }

    /**
     * Lấy dữ liệu warranty_requests đã hoàn tất
     * Thêm filter view để phân biệt KUCHEN vs HUROM
     */
    protected function getWarrantyData(Carbon $fromDate, Carbon $toDate, string $branch, int $view = 1): array
    {
        $query = WarrantyRequest::query()
            ->join('warranty_request_details', 'warranty_requests.id', '=', 'warranty_request_details.warranty_request_id')
            ->select(
                'warranty_requests.id',
                'warranty_requests.serial_number',
                'warranty_requests.serial_thanmay',
                'warranty_requests.product',
                'warranty_requests.branch',
                'warranty_requests.full_name',
                'warranty_requests.phone_number',
                'warranty_requests.staff_received',
                'warranty_requests.received_date',
                'warranty_requests.warranty_end',
                'warranty_requests.shipment_date',
                'warranty_requests.initial_fault_condition',
                'warranty_request_details.replacement',
                'warranty_request_details.solution',
                'warranty_request_details.total',
                'warranty_request_details.replacement_price',
                'warranty_request_details.quantity'
            )
            ->where('warranty_requests.status', 'Đã hoàn tất')
            ->whereBetween('warranty_requests.received_date', [$fromDate, $toDate])
            ->where('warranty_requests.view', $view); // Filter theo view

        if ($branch !== 'all') {
            $query->where('warranty_requests.branch', 'LIKE', '%' . $branch . '%');
        }

        $data = $query->orderBy('warranty_requests.received_date', 'desc')->get();

        return $data->map(function ($item) {
            return [
                'id' => $item->id,
                'serial_number' => $item->serial_number,
                'serial_thanmay' => $item->serial_thanmay,
                'product' => $item->product,
                'branch' => $item->branch,
                'full_name' => $item->full_name,
                'phone_number' => $item->phone_number,
                'staff_received' => $item->staff_received,
                'received_date' => $item->received_date?->format('Y-m-d H:i:s'),
                'warranty_end' => $item->warranty_end,
                'shipment_date' => $item->shipment_date?->format('Y-m-d H:i:s'),
                'initial_fault_condition' => $item->initial_fault_condition,
                'replacement' => $item->replacement,
                'solution' => $item->solution,
                'total' => $item->total,
                'replacement_price' => $item->replacement_price,
                'quantity' => $item->quantity,
            ];
        })->toArray();
    }

    /**
     * Lấy thống kê quy trình làm việc theo từng kỹ thuật viên
     * Thêm filter view để phân biệt KUCHEN vs HUROM
     * Loại bỏ staff_received = 'system'
     */
    protected function getWorkProcessData(Carbon $fromDate, Carbon $toDate, string $branch, int $view = 1): array
    {
        $query = WarrantyRequest::query()
            ->whereBetween('received_date', [$fromDate, $toDate])
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '')
            ->whereRaw('LOWER(TRIM(staff_received)) != ?', ['system']) // Loại bỏ system
            ->where('view', $view); // Filter theo view

        if ($branch !== 'all') {
            $query->where('branch', 'LIKE', '%' . $branch . '%');
        }

        $stats = $query
            ->select(
                'staff_received',
                'branch',
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
                ) as qua_han')
            )
            ->groupBy('staff_received', 'branch')
            ->orderBy('branch')
            ->orderBy('staff_received')
            ->get();

        // Tính tổng số ca của từng chi nhánh
        $branchTotals = $stats->groupBy('branch')->map(function ($branchStats) {
            return $branchStats->sum('tong_tiep_nhan');
        });

        return $stats->map(function ($item) use ($branchTotals) {
            $tongTiepNhan = $item->tong_tiep_nhan ?? 0;
            $branchTotal = $branchTotals->get($item->branch, 0);
            $quaHan = $item->qua_han ?? 0;

            return [
                'staff_received' => $item->staff_received,
                'branch' => $item->branch,
                'tong_tiep_nhan' => $tongTiepNhan,
                'dang_sua_chua' => $item->dang_sua_chua ?? 0,
                'cho_khach_hang_phan_hoi' => $item->cho_khach_hang_phan_hoi ?? 0,
                'da_hoan_tat' => $item->da_hoan_tat ?? 0,
                'qua_han' => $quaHan,
                'ti_le_qua_han' => $tongTiepNhan > 0 ? round(($quaHan / $tongTiepNhan) * 100, 2) : 0,
                'phan_tram_chi_nhanh' => $branchTotal > 0 ? round(($tongTiepNhan / $branchTotal) * 100, 2) : 0,
                'dang_sua_chua_percent' => $tongTiepNhan > 0 ? round((($item->dang_sua_chua ?? 0) / $tongTiepNhan) * 100, 2) : 0,
                'cho_khach_hang_phan_hoi_percent' => $tongTiepNhan > 0 ? round((($item->cho_khach_hang_phan_hoi ?? 0) / $tongTiepNhan) * 100, 2) : 0,
                'da_hoan_tat_percent' => $tongTiepNhan > 0 ? round((($item->da_hoan_tat ?? 0) / $tongTiepNhan) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Tạo dữ liệu tổng hợp nhanh
     */
    protected function createSummary(array $warrantyData, array $workProcessData): array
    {
        $totalRecords = count($warrantyData);
        $totalStaff = count($workProcessData);
        
        $totalTiepNhan = array_sum(array_column($workProcessData, 'tong_tiep_nhan'));
        $totalQuaHan = array_sum(array_column($workProcessData, 'qua_han'));
        $totalHoanTat = array_sum(array_column($workProcessData, 'da_hoan_tat'));

        return [
            'total_records' => $totalRecords,
            'total_staff' => $totalStaff,
            'total_tiep_nhan' => $totalTiepNhan,
            'total_qua_han' => $totalQuaHan,
            'total_hoan_tat' => $totalHoanTat,
            'ti_le_qua_han_tong' => $totalTiepNhan > 0 ? round(($totalQuaHan / $totalTiepNhan) * 100, 2) : 0,
            'ti_le_hoan_tat_tong' => $totalTiepNhan > 0 ? round(($totalHoanTat / $totalTiepNhan) * 100, 2) : 0,
        ];
    }

    /**
     * Ghi log vào file email_report.log
     */
    protected function logSnapshot(string $level, string $message, array $context = []): void
    {
        $logger = Log::channel('email_report');

        if (!method_exists($logger, $level)) {
            $level = 'info';
        }

        $logger->{$level}('[SNAPSHOT] ' . $message, $context);
    }
}