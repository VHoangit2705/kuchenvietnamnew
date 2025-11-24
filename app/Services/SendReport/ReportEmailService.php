<?php

namespace App\Services\SendReport;

use App\Models\KyThuat\WarrantyRequest;
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
    public function generateReportPdf($fromDate, $toDate, $branch = 'all')
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

        // Get work process data
        $workProcessData = $this->getWorkProcessData($fromDate, $toDate, $branch);

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
        $fullPath = storage_path('app/public/' . $pdfPath);

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
     * Nhận dữ liệu quy trình làm việc cho báo cáo
     */
    private function getWorkProcessData($fromDate, $toDate, $branch = 'all')
    {
        $filterFromDate = Carbon::parse($fromDate)->startOfDay();
        $filterToDate = Carbon::parse($toDate)->endOfDay();

        $query = WarrantyRequest::query()
            ->whereBetween('received_date', [$filterFromDate, $filterToDate])
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '');

        if ($branch && $branch !== 'all' && $branch !== '') {
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
                DB::raw('SUM(CASE WHEN status != "Đã hoàn tất" AND status != "Chờ KH phản hồi" AND return_date IS NOT NULL AND return_date < NOW() THEN 1 ELSE 0 END) as qua_han')
            )
            ->groupBy('staff_received', 'branch')
            ->orderBy('branch')
            ->orderBy('staff_received')
            ->get();

        return $stats;
    }
}
