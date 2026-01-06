<?php

namespace App\Services\SendReport;

use App\Models\KyThuat\WarrantyRequest;
use App\Models\KyThuat\WarrantyOverdueRateHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Services\SendReport\ReportHtmlTemplate;

class ReportEmailService
{
    /**
     * Generate PDF report file (landscape orientation, UTF-8)
     */
    public function generateReportPdf($fromDate, $toDate, $branch = 'all', $reportType = null)
    {
        // Get data
        $conditions = [
            'warranty_requests.status' => 'Đã hoàn tất',
            ['warranty_requests.received_date', '>=', $fromDate],
            ['warranty_requests.received_date', '<=', $toDate],
        ];

        if ($branch && $branch !== 'all' && $branch !== '') {
            $conditions[] = ['warranty_requests.branch', 'LIKE', '%' . $branch . '%'];
        }

        $data = WarrantyRequest::getListWarranty($conditions, null);

        // Get work process data từ bảng warranty_overdue_rate_history
        // Truyền reportType để đảm bảo lấy đúng loại báo cáo
        $workProcessData = $this->getWorkProcessData($fromDate, $toDate, $branch, $reportType);

        // Format dates for display and filename
        $fromDateFormatted = Carbon::parse($fromDate)->format('d/m/Y');
        $toDateFormatted = Carbon::parse($toDate)->format('d/m/Y');

        // Get branch name
        $branchMap = [
            'kchanoi' => 'KUCHEN HÀ NỘI',
            'kcvinh' => 'KUCHEN VINH',
            'kchcm' => 'KUCHEN HCM',
            'hrhanoi' => 'HUROM HÀ NỘI',
            'hrvinh' => 'HUROM VINH',
            'hrhcm' => 'HUROM HCM',
        ];
        $branchName = $branchMap[$branch] ?? "Tất cả chi nhánh";

        // Build HTML report using template
        $template = new ReportHtmlTemplate();
        $html = $template->buildHtml($data, $workProcessData, $fromDateFormatted, $toDateFormatted, $branchName);

        // Tạo tên tệp duy nhất
        $fileName = 'bao_cao_bao_hanh_' . Str::slug($fromDateFormatted . '_' . $toDateFormatted) . '_' . time() . '.pdf';
        $pdfPath = 'reports/' . $fileName;
        // Lưu vào storage/app/reports/ (private, không phải public)
        $fullPath = storage_path('app/' . $pdfPath);

        // Đảm bảo thư mục tồn tại
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Tạo PDF từ HTML bằng Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultMediaType', 'screen');
        $options->set('defaultPaperSize', 'a4');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        // Save PDF to file
        file_put_contents($fullPath, $dompdf->output());

        return [
            'pdf_path' => $pdfPath,
            'file_name' => $fileName,
            'full_path' => $fullPath
        ];
    }

    /**
     * Nhận dữ liệu quy trình làm việc cho báo cáo từ bảng warranty_overdue_rate_history
     * Đảm bảo dữ liệu không bị thay đổi sau khi đã lưu (snapshot tại thời điểm lưu)
     */
    private function getWorkProcessData($fromDate, $toDate, $branch = 'all', $reportType = null)
    {
        $filterFromDate = Carbon::parse($fromDate)->startOfDay();
        $filterToDate = Carbon::parse($toDate)->endOfDay();
        
        // Tính toán các số liệu trực tiếp từ warranty_requests (real-time)
        $query = WarrantyRequest::query()
            ->whereBetween('received_date', [$filterFromDate, $filterToDate])
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '');

        if ($branch && $branch !== 'all' && $branch !== '') {
            $query->where('branch', 'LIKE', '%' . $branch . '%');
        }

        // Tính toán các số liệu real-time
        $stats = $query
            ->select(
                'staff_received',
                'branch',
                DB::raw('COUNT(*) as tong_tiep_nhan'),
                DB::raw('SUM(CASE WHEN status = "Đang sửa chữa" THEN 1 ELSE 0 END) as dang_sua_chua'),
                DB::raw('SUM(CASE WHEN status = "Chờ KH phản hồi" THEN 1 ELSE 0 END) as cho_khach_hang_phan_hoi'),
                DB::raw('SUM(CASE WHEN status = "Đã hoàn tất" THEN 1 ELSE 0 END) as da_hoan_tat'),
                DB::raw('SUM(CASE WHEN status != "Đã hoàn tất" AND status != "Chờ KH phản hồi" AND return_date IS NOT NULL AND return_date < NOW() THEN 1 ELSE 0 END) as qua_han')
            )
            ->groupBy('staff_received', 'branch')
            ->orderBy('branch')
            ->orderBy('staff_received')
            ->get();

        // Xác định report_type nếu không được truyền vào
        if (!$reportType) {
            // Nếu khoảng thời gian <= 14 ngày: ưu tiên weekly
            // Nếu > 14 ngày: ưu tiên monthly
            $daysDiff = $filterFromDate->diffInDays($filterToDate);
            $reportType = ($daysDiff <= 14) ? 'weekly' : 'monthly';
            
            // Kiểm tra xem có dữ liệu monthly không (ưu tiên monthly nếu có)
            $hasMonthly = WarrantyOverdueRateHistory::query()
                ->whereNotNull('staff_received')
                ->where('staff_received', '!=', '')
                ->where('report_type', 'monthly')
                ->where(function($q) use ($filterFromDate, $filterToDate) {
                    $q->where('from_date', '<=', $filterToDate->toDateString())
                      ->where('to_date', '>=', $filterFromDate->toDateString());
                })
                ->exists();
            
            if ($hasMonthly) {
                $reportType = 'monthly';
            }
        }

        // Lấy "Tỉ lệ trễ ca bảo hành (%)" từ bảng warranty_overdue_rate_history
        $overdueRates = WarrantyOverdueRateHistory::query()
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '')
            ->where('report_type', $reportType)
            ->where(function($q) use ($filterFromDate, $filterToDate) {
                $q->where('from_date', '<=', $filterToDate->toDateString())
                  ->where('to_date', '>=', $filterFromDate->toDateString());
            });

        if ($branch && $branch !== 'all' && $branch !== '') {
            $overdueRates->where('branch', 'LIKE', '%' . $branch . '%');
        }

        // Lấy tỉ lệ quá hạn đã lưu, nhóm theo staff_received và branch
        $overdueRatesData = $overdueRates
            ->select(
                'staff_received',
                'branch',
                DB::raw('AVG(ti_le_qua_han) as ti_le_qua_han') // Lấy trung bình nếu có nhiều bản ghi
            )
            ->groupBy('staff_received', 'branch')
            ->get()
            ->keyBy(function ($item) {
                return $item->staff_received . '|' . $item->branch;
            });

        // Tính tổng số ca của từng chi nhánh
        $branchTotals = $stats->groupBy('branch')->map(function ($branchStats) {
            return $branchStats->sum('tong_tiep_nhan');
        });

        // Merge dữ liệu: số liệu real-time + tỉ lệ quá hạn từ history
        $stats = $stats->map(function ($item) use ($branchTotals, $overdueRatesData) {
            $tongTiepNhan = $item->tong_tiep_nhan ?? 0;
            $branchTotal = $branchTotals->get($item->branch, 0);
            
            // Phần trăm so với chi nhánh
            $item->phan_tram_chi_nhanh = $branchTotal > 0 
                ? ($tongTiepNhan / $branchTotal) * 100 
                : 0;
            
            // Lấy tỉ lệ quá hạn từ history (nếu có), nếu không thì tính từ real-time
            $key = ($item->staff_received ?? '') . '|' . ($item->branch ?? '');
            $overdueRate = $overdueRatesData->get($key);
            $item->ti_le_qua_han = $overdueRate ? $overdueRate->ti_le_qua_han : null;
            
            // Nếu không có tỉ lệ từ history, tính từ real-time
            if ($item->ti_le_qua_han === null) {
                $quaHan = $item->qua_han ?? 0;
                $item->ti_le_qua_han = $tongTiepNhan > 0 
                    ? ($quaHan / $tongTiepNhan) * 100 
                    : 0;
            }
            
            // Phần trăm các trạng thái (so với tổng tiếp nhận của nhân viên)
            $item->dang_sua_chua_percent = $tongTiepNhan > 0 
                ? (($item->dang_sua_chua ?? 0) / $tongTiepNhan) * 100 
                : 0;
            
            $item->cho_khach_hang_phan_hoi_percent = $tongTiepNhan > 0 
                ? (($item->cho_khach_hang_phan_hoi ?? 0) / $tongTiepNhan) * 100 
                : 0;
            
            $item->da_hoan_tat_percent = $tongTiepNhan > 0 
                ? (($item->da_hoan_tat ?? 0) / $tongTiepNhan) * 100 
                : 0;
            
            return $item;
        });

        return $stats;
    }
}
