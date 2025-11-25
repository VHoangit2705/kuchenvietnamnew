<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\KyThuat\WarrantyRequest;
use App\Models\KyThuat\WarrantyOverdueRateHistory;
use App\Models\Kho\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class ReportController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function Index(Request $request)
    {
        $fromDate = $request->fromDate ?? Carbon::now()->startOfMonth()->toDateString();
        $toDate = $request->toDate ?? Carbon::today()->toDateString();
        //$parts = explode(' ', session('zone'));
        //$brand = strtoupper(session('brand')) . ' ' . end($parts);
        $parts = explode(' ', session('zone'));
        $zoneWithoutFirst = implode(' ', array_slice($parts, 1));
        $brand = strtoupper(session('brand')) . ' ' . $zoneWithoutFirst;
        if(session('position') == 'admin' || session('position') == 'quản trị viên'){
            $brand = '';
        }

        // Tạo điều kiện mặc định
        $conditions = [
            'warranty_requests.status' => 'Đã hoàn tất',
            ['warranty_requests.received_date', '>=', $fromDate],
            ['warranty_requests.received_date', '<=', $toDate],
        ];

        // Bổ sung điều kiện nếu có filter
        $productInput = $request->product;

        if ($request->replacement) {
            $conditions[] = ['warranty_request_details.replacement', 'LIKE', '%' . $request->replacement . '%'];
        }

        if ($request->warrantySelect && $request->warrantySelect !== 'tatca') {
            if ($request->warrantySelect === 'conbaohanh') {
                $conditions[] = ['warranty_requests.warranty_end', '>=', DB::raw('warranty_requests.received_date')];
            } else {
                $conditions[] = ['warranty_requests.warranty_end', '<', DB::raw('warranty_requests.received_date')];
            }
        }

        // Chỉ filter theo branch nếu user chọn một chi nhánh cụ thể
        // Nếu chọn "Tất cả chi nhánh" hoặc không chọn gì, hiển thị tất cả
        if ($request->branch && $request->branch !== 'all' && $request->branch !== '') {
            $conditions[] = ['warranty_requests.branch', 'LIKE', '%' . $request->branch . '%'];
        }
        // Bỏ điều kiện else if để hiển thị tất cả chi nhánh khi không chọn

        if ($request->staff_received) {
            $conditions[] = ['warranty_requests.staff_received', 'LIKE', '%' . $request->staff_received . '%'];
        }

        if ($request->solution && $request->solution !== 'tatca') {
            $conditions[] = ['warranty_request_details.solution', 'LIKE', '%' . $request->solution . '%'];
        }

        // Lấy dữ liệu
        $data = WarrantyRequest::getListWarranty($conditions, $productInput);

        //Lấy linh kiện
        $linhkien = Product::where('view', '2')->select('product_name')->get();

        // Tính toán counts cho tabs
        $activeTab = $request->get('tab', 'warranty');
        
        // Tính toán thống kê quá trình làm việc
        $workProcessData = $this->getWorkProcessStats($request, $brand, $fromDate, $toDate);
        $counts = [
            'warranty' => $data->count(),
            'work_process' => $workProcessData->count(), // Đếm số dòng (số kỹ thuật viên) trong bảng
        ];

        session(['dataExport' => $data]);
        session(['workProcessDataExport' => $workProcessData]);
        
        // Nếu là AJAX request, trả về JSON với tab header và table content
        if ($request->ajax()) {
            // Cập nhật lại session khi filter qua AJAX
            session(['dataExport' => $data]);
            session(['workProcessDataExport' => $workProcessData]);
            
            $response = [
                'tab' => view('components.tab_header_report_warranty', [
                    'activeTab' => $activeTab,
                    'counts' => $counts,
                ])->render(),
                'table' => $activeTab == 'work_process' 
                    ? view('report.table_content_report.table_content_work_process', [
                        'workProcessData' => $workProcessData
                    ])->render()
                    : view('report.table_content_report.table_content', [
                        'data' => $data
                    ])->render(),
            ];
            
            // Thêm phần filter reportType nếu đang ở tab work_process
            if ($activeTab == 'work_process') {
                $reportType = $request->reportType ?? 'weekly';
                $toDateCarbon = Carbon::parse($toDate);
                $fromDateCarbon = Carbon::parse($fromDate);
                $weekNumber = $toDateCarbon->weekOfMonth;
                $monthNumber = $toDateCarbon->month;
                
                // Lấy thông tin from_date và to_date từ bản ghi đầu tiên trong database (nếu có)
                $firstRecord = WarrantyOverdueRateHistory::where('report_type', $reportType)
                    ->whereNotNull('staff_received')
                    ->where('staff_received', '!=', '')
                    ->where(function($q) use ($fromDateCarbon, $toDateCarbon) {
                        $q->where('from_date', '<=', $toDateCarbon->toDateString())
                          ->where('to_date', '>=', $fromDateCarbon->toDateString());
                    })
                    ->orderBy('to_date', 'desc')
                    ->first();
                
                $actualFromDate = $firstRecord ? $firstRecord->from_date : $fromDateCarbon->toDateString();
                $actualToDate = $firstRecord ? $firstRecord->to_date : $toDateCarbon->toDateString();
                
                $response['filter'] = view('report.partials.report_type_filter', [
                    'reportType' => $reportType,
                    'weekNumber' => $weekNumber,
                    'monthNumber' => $monthNumber,
                    'fromDate' => $actualFromDate,
                    'toDate' => $actualToDate,
                ])->render();
            }
            
            return response()->json($response);
        }
        
        return view('report.listwarranty', [
            'data' => $data,
            'workProcessData' => $workProcessData,
            'fromDate' => Carbon::parse($fromDate),
            'toDate' => Carbon::parse($toDate),
            'userBranch' => $brand,
            'linhkien' => $linhkien,
            'activeTab' => $activeTab,
            'counts' => $counts,
        ]);
    }

    public function RecommentProduct(Request $request)
    {
        $search = $request->query('query');
        $products = DB::table('warranty_requests')->select('product')->distinct()->where('product', 'LIKE', "%{$search}%")->pluck('product')->toArray();
        $serials = DB::table('warranty_requests')->select('serial_number')->distinct()->where('serial_number', 'LIKE', "%{$search}%")->pluck('serial_number')->toArray();
        $recomments = array_merge($products, $serials);
        return response()->json($recomments);
    }

    public function RecommentProductPart(Request $request)
    {
        $search = $request->query('query');
        $productParts = DB::table('warranty_request_details')->select('replacement')->distinct()->where('replacement', 'LIKE', "%{$search}%")->pluck('replacement')->toArray();
        return response()->json($productParts);
    }

    public function RecommentStaff(Request $request)
    {
        $search = $request->query('query');
        $staffName = DB::table('warranty_requests')->select('staff_received')->distinct()->where('staff_received', 'LIKE', "%{$search}%")->pluck('staff_received')->toArray();
        return response()->json($staffName);
    }

    /**
     * Lấy thống kê quá trình làm việc
     * - Các số liệu (tong_tiep_nhan, dang_sua_chua, etc.) tính trực tiếp từ warranty_requests (real-time)
     * - Chỉ lấy "Tỉ lệ trễ ca bảo hành (%)" từ bảng warranty_overdue_rate_history
     */
    private function getWorkProcessStats(Request $request, $brand, $fromDate, $toDate)
    {
        // Sử dụng filter ngày từ form (đã được parse trong method Index)
        $filterFromDate = Carbon::parse($fromDate)->startOfDay();
        $filterToDate = Carbon::parse($toDate)->endOfDay();
        
        // Tính toán các số liệu trực tiếp từ warranty_requests (real-time)
        $query = WarrantyRequest::query()
            ->whereBetween('received_date', [$filterFromDate, $filterToDate])
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '');
        
        // Chỉ filter theo branch nếu user chọn một chi nhánh cụ thể
        if ($request->branch && $request->branch !== 'all' && $request->branch !== '') {
            $query->where('branch', 'LIKE', '%' . $request->branch . '%');
        }
        
        // Filter theo kỹ thuật viên nếu có
        if ($request->staff_received) {
            $query->where('staff_received', 'LIKE', '%' . $request->staff_received . '%');
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
        
        // Lấy "Tỉ lệ trễ ca bảo hành (%)" từ bảng warranty_overdue_rate_history
        $reportType = $request->reportType ?? 'weekly';
        $overdueRates = WarrantyOverdueRateHistory::query()
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '')
            ->where('report_type', $reportType)
            ->where(function($q) use ($filterFromDate, $filterToDate) {
                $q->where('from_date', '<=', $filterToDate->toDateString())
                  ->where('to_date', '>=', $filterFromDate->toDateString());
            });
        
        if ($request->branch && $request->branch !== 'all' && $request->branch !== '') {
            $overdueRates->where('branch', 'LIKE', '%' . $request->branch . '%');
        }
        
        if ($request->staff_received) {
            $overdueRates->where('staff_received', 'LIKE', '%' . $request->staff_received . '%');
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
            
            // Lấy tỉ lệ quá hạn từ history (nếu có)
            $key = ($item->staff_received ?? '') . '|' . ($item->branch ?? '');
            $overdueRate = $overdueRatesData->get($key);
            $item->ti_le_qua_han = $overdueRate ? $overdueRate->ti_le_qua_han : null;
            
            // Phần trăm các trạng thái (so với tổng tiếp nhận của nhân viên)
            // Sẽ được tính trong view bằng HTML
            $item->dang_sua_chua_percent = 0; // Sẽ tính trong view
            $item->cho_khach_hang_phan_hoi_percent = 0; // Sẽ tính trong view
            $item->da_hoan_tat_percent = 0; // Sẽ tính trong view
            
            return $item;
        });
        
        return $stats;
    }

    //xuất excel
    public function GetExportExcel(Request $request)
    {
        // Giới hạn xuất mỗi 1 phút
        if (session()->has('export_time')) {
            $now = Carbon::now('Asia/Ho_Chi_Minh')->setMicrosecond(0);
            $exportTime = Carbon::parse(session('export_time'))->timezone('Asia/Ho_Chi_Minh')->setMicrosecond(0);
            $exportLimit = $exportTime->addSeconds(60);
            if ($now->lt($exportLimit)) {
                $diff = $exportLimit->diffInSeconds($now, true);
                return response()->json([
                    'status' => "error",
                    'message' => 'Bạn đã xuất file rồi. Vui lòng thử lại sau ' . $diff . ' giây.'
                ], 200);
            }
        }

        $data = session('dataExport');
        if ($data->isEmpty()) {
            return response()->json([
                'status' => "error",
                'message' => 'Không có dữ liệu để xuất.'
            ], 200);
        }

        $branchMap = [
            'kchanoi' => 'KUCHEN HÀ NỘI',
            'kcvinh' => 'KUCHEN VINH',
            'kchcm' => 'KUCHEN HCM',
            'hrhanoi' => 'HUROM HÀ NỘI',
            'hrvinh' => 'HUROM VINH',
            'hrhcm' => 'HUROM HCM',
        ];
        $branch = $branchMap[$request->query("branch", "")] ?? "Tất cả chi nhánh";
        $fromDate = $request->query('fromDate') ? Carbon::parse($request->query('fromDate'))->format('d/m/Y') : "";
        $toDate = $request->query('toDate') ? Carbon::parse($request->query('toDate'))->format('d/m/Y') : "";
        // Tạo file Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('B2:H2');
        $sheet->setCellValue('A1', 'BÁO CÁO THỐNG KÊ TRƯỜNG HỢP BẢO HÀNH');
        $sheet->setCellValue('B2', 'Chi nhánh: ' . $branch . '     Từ ngày: ' . $fromDate . ' đến ngày: ' . $toDate);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('B2')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2')->getFont()->setBold(true);

        $sheet->fromArray([
            ['STT', 'Mã serial', 'Mã serial thân máy', 'Tên sản phẩm', 'Chi nhánh', 'Khách hàng', 'Số điện thoại', 'Kỹ thuật viên', 'Ngày tiếp nhận', 'Lỗi ban đầu', 'Ngày xuất kho', 'Tình trạng BH', 'Linh kiện', 'Đơn giá', 'Số lượng', 'Thành tiền', 'Khách hàng chi trả']
        ], NULL, 'A3');
        $sheet->getStyle('A3:Q3')->getFont()->setBold(true);
        $sheet->getStyle('A3:Q3')->getAlignment()->setVertical('center');
        
        // Ghi dữ liệu từ dòng 2
        $row = 4;
        $stt = 1;
        $countBH = 0;
        $countLKBH = $priceLKBH = $PhiBH = 0; // còn hạn bảo hành
        $countLKKBH = $priceLKKBH = $PhiHBH = 0; // hết hạn bảo hành
        foreach ($data as $item) {
            $BH ='Hết hạn BH';
            if($item->warranty_end >= $item->received_date){
                $BH = 'Còn hạn BH';
                $countBH ++;
                if($item->replacement){
                    $countLKBH++;
                    $priceLKBH += $item->replacement_price * $item->quantity;
                    $PhiBH += $item->total;
                }
            }
            else{
                if($item->replacement){
                    $countLKKBH++;
                    $priceLKKBH += $item->replacement_price * $item->quantity;
                    $PhiHBH += $item->total;
                }
            }
            
            // Ghi Excel
            $sheet->fromArray([
                $stt++,
                $item->serial_number,
                $item->serial_thanmay,
                $item->product,
                $item->branch,
                $item->full_name,
                $item->phone_number,
                $item->staff_received,
                Carbon::parse($item->received_date)->format('d/m/Y'),
                $item->initial_fault_condition,
                Carbon::parse($item->shipment_date)->format('d/m/Y'),
                $BH,
                $item->replacement,
                $item->replacement_price,
                $item->quantity,
                $item->quantity * $item->replacement_price,
                $item->total
            ], NULL, "A{$row}");
            $row++;
        }

        // Auto column width
        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Summary table
        $row += 2;
        $sheet->fromArray([['Phân loại', 'Số lượng ca bảo hành', 'Số lượng linh kiện đã thay', 'Chi phí linh kiện', 'Chi phí bảo hành']], NULL, "B{$row}");
        $sheet->getStyle("B{$row}:F{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}:F{$row}")->getBorders()->getAllBorders()->setBorderStyle('thin');

        $summaryData = [
            ['Còn bảo hành', $countBH, $countLKBH, $priceLKBH, $PhiBH],
            ['Hết bảo hành', $data->count() - $countBH, $countLKKBH, $priceLKKBH, $PhiHBH],
            ['Tổng', $data->count(), $countLKBH + $countLKKBH, $priceLKBH + $priceLKKBH, $PhiBH + $PhiHBH],
        ];

        foreach ($summaryData as $rowData) {
            $row++;
            $sheet->fromArray($rowData, NULL, "B{$row}");
            $sheet->getStyle("B{$row}:F{$row}")->getBorders()->getAllBorders()->setBorderStyle('thin');
        }

        // Tạo sheet mới: Quá trình làm việc
        $workProcessData = session('workProcessDataExport');
        if ($workProcessData && $workProcessData->isNotEmpty()) {
            $workProcessSheet = $spreadsheet->createSheet();
            $workProcessSheet->setTitle('Quá trình làm việc');
            
            // Tiêu đề chính
            $workProcessSheet->mergeCells('A1:M1');
            $workProcessSheet->setCellValue('A1', 'BÁO CÁO TỔNG HỢP QUÁ TRÌNH LÀM VIỆC CỦA NHÂN VIÊN');
            $workProcessSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $workProcessSheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
            $workProcessSheet->getRowDimension(1)->setRowHeight(25);
            
            // Thông tin filter
            $workProcessSheet->mergeCells('A2:M2');
            $workProcessSheet->setCellValue('A2', 'Chi nhánh: ' . $branch . '     Từ ngày: ' . $fromDate . ' đến ngày: ' . $toDate);
            $workProcessSheet->getStyle('A2')->getAlignment()->setHorizontal('right');
            $workProcessSheet->getStyle('A2')->getFont()->setBold(true);
            $workProcessSheet->getRowDimension(2)->setRowHeight(20);
            
            // Ghi chú giải thích
            $workProcessSheet->mergeCells('A3:M3');
            $workProcessSheet->setCellValue('A3', 'Ghi chú: - "% so với CN" = Phần trăm số ca nhân viên tiếp nhận so với tổng số ca của chi nhánh');
            $workProcessSheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
            $workProcessSheet->getStyle('A3')->getAlignment()->setHorizontal('left')->setVertical('center');
            $workProcessSheet->getRowDimension(3)->setRowHeight(18);
            
            $workProcessSheet->mergeCells('A4:M4');
            $workProcessSheet->setCellValue('A4', '        - Các phần trăm khác (%) = Phần trăm số ca của từng trạng thái so với tổng số ca nhân viên đó tiếp nhận');
            $workProcessSheet->getStyle('A4')->getFont()->setItalic(true)->setSize(10);
            $workProcessSheet->getStyle('A4')->getAlignment()->setHorizontal('left')->setVertical('center');
            $workProcessSheet->getRowDimension(4)->setRowHeight(18);
            
            // Bảng header
            $workProcessSheet->fromArray([
                ['STT', 'Chi nhánh', 'Tên kỹ thuật viên', 'Tổng tiếp nhận', '% so với CN', 'Đang sửa chữa', 'Đang sửa chữa %', 'Chờ KH phản hồi', 'Chờ KH phản hồi %', 'Quá hạn', 'Quá hạn %', 'Đã hoàn tất', 'Đã hoàn tất %']
            ], NULL, 'A5');
            $workProcessSheet->getStyle('A5:M5')->getFont()->setBold(true);
            $workProcessSheet->getStyle('A5:M5')->getAlignment()->setVertical('center')->setHorizontal('center');
            $workProcessSheet->getStyle('A5:M5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD3D3D3');
            $workProcessSheet->getStyle('A5:M5')->getBorders()->getAllBorders()->setBorderStyle('thin');
            $workProcessSheet->getRowDimension(5)->setRowHeight(25);
            
            // Ghi dữ liệu
            $workRow = 6;
            $workStt = 1;
            foreach ($workProcessData as $item) {
                $workProcessSheet->setCellValue('A' . $workRow, $workStt++);
                $workProcessSheet->setCellValue('B' . $workRow, $item->branch ?? 'N/A');
                $workProcessSheet->setCellValue('C' . $workRow, $item->staff_received ?? 'N/A');
                $workProcessSheet->setCellValue('D' . $workRow, $item->tong_tiep_nhan ?? 0);
                $workProcessSheet->getStyle('D' . $workRow)->getAlignment()->setHorizontal('center');
                
                // Phần trăm so với chi nhánh
                $workProcessSheet->setCellValue('E' . $workRow, ($item->phan_tram_chi_nhanh ?? 0) / 100);
                $workProcessSheet->getStyle('E' . $workRow)->getNumberFormat()->setFormatCode('0.00%');
                $workProcessSheet->getStyle('E' . $workRow)->getAlignment()->setHorizontal('center');
                
                $workProcessSheet->setCellValue('F' . $workRow, $item->dang_sua_chua ?? 0);
                $workProcessSheet->getStyle('F' . $workRow)->getAlignment()->setHorizontal('center');
                $workProcessSheet->setCellValue('G' . $workRow, ($item->dang_sua_chua_percent ?? 0) / 100);
                $workProcessSheet->getStyle('G' . $workRow)->getNumberFormat()->setFormatCode('0.00%');
                $workProcessSheet->getStyle('G' . $workRow)->getAlignment()->setHorizontal('center');
                
                $workProcessSheet->setCellValue('H' . $workRow, $item->cho_khach_hang_phan_hoi ?? 0);
                $workProcessSheet->getStyle('H' . $workRow)->getAlignment()->setHorizontal('center');
                $workProcessSheet->setCellValue('I' . $workRow, ($item->cho_khach_hang_phan_hoi_percent ?? 0) / 100);
                $workProcessSheet->getStyle('I' . $workRow)->getNumberFormat()->setFormatCode('0.00%');
                $workProcessSheet->getStyle('I' . $workRow)->getAlignment()->setHorizontal('center');
                
                $workProcessSheet->setCellValue('J' . $workRow, $item->qua_han ?? 0);
                $workProcessSheet->getStyle('J' . $workRow)->getAlignment()->setHorizontal('center');
                $workProcessSheet->setCellValue('K' . $workRow, ($item->qua_han_percent ?? 0) / 100);
                $workProcessSheet->getStyle('K' . $workRow)->getNumberFormat()->setFormatCode('0.00%');
                $workProcessSheet->getStyle('K' . $workRow)->getAlignment()->setHorizontal('center');
                
                $workProcessSheet->setCellValue('L' . $workRow, $item->da_hoan_tat ?? 0);
                $workProcessSheet->getStyle('L' . $workRow)->getAlignment()->setHorizontal('center');
                $workProcessSheet->setCellValue('M' . $workRow, ($item->da_hoan_tat_percent ?? 0) / 100);
                $workProcessSheet->getStyle('M' . $workRow)->getNumberFormat()->setFormatCode('0.00%');
                $workProcessSheet->getStyle('M' . $workRow)->getAlignment()->setHorizontal('center');
                
                // Thêm border cho từng dòng
                $workProcessSheet->getStyle('A' . $workRow . ':M' . $workRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                
                $workRow++;
            }
            
            // Auto column width và format
            foreach (range('A', 'M') as $col) {
                $workProcessSheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Căn giữa các cột số
            $workProcessSheet->getStyle('A5:A' . ($workRow - 1))->getAlignment()->setHorizontal('center');
            $workProcessSheet->getStyle('B5:C' . ($workRow - 1))->getAlignment()->setHorizontal('left');
            
            // Wrap text cho các cột dài
            $workProcessSheet->getStyle('B5:C' . ($workRow - 1))->getAlignment()->setWrapText(true);
            
            // Thêm bảng tổng hợp "Tổng tiếp nhận" theo chi nhánh
            $workRow += 5; // Tạo khoảng cách
            
            // Tính tổng tiếp nhận của từng chi nhánh
            $branchTotals = $workProcessData->groupBy('branch')->map(function ($branchStats) {
                return $branchStats->sum('tong_tiep_nhan');
            });
            
            // Lấy danh sách các chi nhánh có trong dữ liệu
            $branches = $branchTotals->keys()->toArray();
            $numBranches = count($branches);
            
            if ($numBranches > 0) {
                // Header "Tổng tiếp nhận" - merge các cột
                $startCol = 'A';
                // Tính cột cuối cùng (hỗ trợ tối đa 26 cột A-Z)
                $endColIndex = min($numBranches - 1, 25); // Tối đa Z (26 cột)
                $endCol = chr(ord('A') + $endColIndex);
                $workProcessSheet->mergeCells($startCol . $workRow . ':' . $endCol . $workRow);
                $workProcessSheet->setCellValue($startCol . $workRow, 'TỔNG CA BẢO HÀNH TIẾP NHẬN');
                $workProcessSheet->getStyle($startCol . $workRow)->getFont()->setBold(true);
                $workProcessSheet->getStyle($startCol . $workRow)->getAlignment()->setHorizontal('center')->setVertical('center');
                $workProcessSheet->getStyle($startCol . $workRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD3D3D3');
                $workProcessSheet->getStyle($startCol . $workRow . ':' . $endCol . $workRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                $workProcessSheet->getRowDimension($workRow)->setRowHeight(25);
                
                // Dòng tên chi nhánh
                $workRow++;
                $colIndex = 0;
                foreach ($branches as $branchName) {
                    if ($colIndex >= 26) break; // Giới hạn 26 cột
                    $col = chr(ord('A') + $colIndex);
                    $workProcessSheet->setCellValue($col . $workRow, $branchName);
                    $workProcessSheet->getStyle($col . $workRow)->getFont()->setBold(true);
                    $workProcessSheet->getStyle($col . $workRow)->getAlignment()->setHorizontal('center')->setVertical('center');
                    $workProcessSheet->getStyle($col . $workRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                    $colIndex++;
                }
                $workProcessSheet->getRowDimension($workRow)->setRowHeight(20);
                
                // Dòng số liệu
                $workRow++;
                $colIndex = 0;
                foreach ($branches as $branchName) {
                    if ($colIndex >= 26) break; // Giới hạn 26 cột
                    $col = chr(ord('A') + $colIndex);
                    $workProcessSheet->setCellValue($col . $workRow, $branchTotals->get($branchName, 0));
                    $workProcessSheet->getStyle($col . $workRow)->getAlignment()->setHorizontal('center')->setVertical('center');
                    $workProcessSheet->getStyle($col . $workRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                    $colIndex++;
                }
                $workProcessSheet->getRowDimension($workRow)->setRowHeight(20);
            }
        }
        
        // Xuất file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        session(['export_time' => now('Asia/Ho_Chi_Minh')]);

        return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="bao_cao_bao_hanh.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * View PDF report by filename
     */
    public function viewReportPdf($filename)
    {
        $filename = basename($filename);

        $filePath = public_path('storage/reports/' . $filename . '.pdf');

        if (!file_exists($filePath)) {
            Log::error("PDF file not found", [
                'filename' => $filename,
                'filePath' => $filePath,
            ]);
            abort(404, 'File không tồn tại: ' . $filename);
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }

}
