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
use App\Models\Kho\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Style\Fill;


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

        // Xác định view: 1 = kuchen, 3 = hurom
        $view = session('brand') === 'hurom' ? 3 : 1;

        // Tạo điều kiện mặc định
        $conditions = [
            'warranty_requests.status' => 'Đã hoàn tất',
            ['warranty_requests.received_date', '>=', $fromDate],
            ['warranty_requests.received_date', '<=', $toDate],
            ['warranty_requests.view', '=', $view],
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
                    ->whereRaw('LOWER(TRIM(staff_received)) != ?', ['system'])
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
        
        // Xác định view: 1 = kuchen, 3 = hurom
        $view = session('brand') === 'hurom' ? 3 : 1;
        
        $productsQuery = DB::table('warranty_requests')
            ->select('product')
            ->distinct()
            ->where('product', 'LIKE', "%{$search}%")
            ->where('view', '=', $view);
        
        $serialsQuery = DB::table('warranty_requests')
            ->select('serial_number')
            ->distinct()
            ->where('serial_number', 'LIKE', "%{$search}%")
            ->where('view', '=', $view);
        
        $products = $productsQuery->pluck('product')->toArray();
        $serials = $serialsQuery->pluck('serial_number')->toArray();
        $recomments = array_merge($products, $serials);
        return response()->json($recomments);
    }

    public function RecommentProductPart(Request $request)
    {
        $search = $request->query('query');
        
        // Xác định view: 1 = kuchen, 3 = hurom
        $view = session('brand') === 'hurom' ? 3 : 1;
        
        $productPartsQuery = DB::table('warranty_request_details')
            ->join('warranty_requests', 'warranty_request_details.warranty_request_id', '=', 'warranty_requests.id')
            ->select('warranty_request_details.replacement')
            ->distinct()
            ->where('warranty_request_details.replacement', 'LIKE', "%{$search}%")
            ->where('warranty_requests.view', '=', $view);
        
        $productParts = $productPartsQuery->pluck('replacement')->toArray();
        return response()->json($productParts);
    }

    public function RecommentStaff(Request $request)
    {
        $search = $request->query('query');
        
        // Xác định view: 1 = kuchen, 3 = hurom
        $view = session('brand') === 'hurom' ? 3 : 1;
        
        $staffNameQuery = DB::table('warranty_requests')
            ->select('staff_received')
            ->distinct()
            ->where('staff_received', 'LIKE', "%{$search}%")
            ->where('view', '=', $view);
        
        $staffName = $staffNameQuery->pluck('staff_received')->toArray();
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
        
        // Xác định view: 1 = kuchen, 3 = hurom
        $view = session('brand') === 'hurom' ? 3 : 1;
        
        // Tính toán các số liệu trực tiếp từ warranty_requests (real-time)
        $query = WarrantyRequest::query()
            ->whereBetween('received_date', [$filterFromDate, $filterToDate])
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '')
            ->where('view', '=', $view);
        
        // Chỉ filter theo branch nếu user chọn một chi nhánh cụ thể
        if ($request->branch && $request->branch !== 'all' && $request->branch !== '') {
            $query->where('branch', 'LIKE', '%' . $request->branch . '%');
        }
        
        // Bỏ tên kỹ thuật viên có tên "system"
        $query->whereRaw('LOWER(TRIM(staff_received)) != ?', ['system']);
        
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
        
        // Lấy "Tỉ lệ trễ ca bảo hành (%)" từ bảng warranty_overdue_rate_history
        $reportType = $request->reportType ?? 'weekly';
        $overdueRates = WarrantyOverdueRateHistory::query()
            ->whereNotNull('staff_received')
            ->where('staff_received', '!=', '')
            ->whereRaw('LOWER(TRIM(staff_received)) != ?', ['system'])
            ->where('report_type', $reportType)
            ->where(function($q) use ($filterFromDate, $filterToDate) {
                $q->where('from_date', '<=', $filterToDate->toDateString())
                  ->where('to_date', '>=', $filterFromDate->toDateString());
            });
        
        // Filter theo branch pattern để phân biệt kuchen và hurom
        $branchPattern = $view === 3 ? 'hurom%' : 'kuchen%';
        $overdueRates->whereRaw('LOWER(branch) LIKE ?', [strtolower($branchPattern)]);
        
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
        $fromDateRaw = $request->query('fromDate') ? Carbon::parse($request->query('fromDate'))->toDateString() : Carbon::now()->startOfMonth()->toDateString();
        $toDateRaw = $request->query('toDate') ? Carbon::parse($request->query('toDate'))->toDateString() : Carbon::today()->toDateString();
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
            // Đồng bộ dữ liệu với bản PDF (tính % theo chi nhánh và theo tổng tiếp nhận của nhân viên)
            $workBranchTotals = $workProcessData->groupBy('branch')->map(function ($branchStats) {
                return $branchStats->sum('tong_tiep_nhan');
            });

            $workProcessData = $workProcessData->map(function ($item) use ($workBranchTotals) {
                $tongTiepNhan = $item->tong_tiep_nhan ?? 0;
                $branchTotal = $workBranchTotals->get($item->branch, 0);

                $item->phan_tram_chi_nhanh = $branchTotal > 0
                    ? ($tongTiepNhan / $branchTotal) * 100
                    : 0;

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

            $workProcessSheet = $spreadsheet->createSheet();
            $workProcessSheet->setTitle('Quá trình làm việc');
            
            // Tiêu đề chính
            $workProcessSheet->mergeCells('A1:L1');
            $workProcessSheet->setCellValue('A1', 'BÁO CÁO TỔNG HỢP QUÁ TRÌNH LÀM VIỆC CỦA NHÂN VIÊN');
            $workProcessSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $workProcessSheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
            $workProcessSheet->getRowDimension(1)->setRowHeight(25);
            
            // Thông tin filter
            $workProcessSheet->mergeCells('A2:L2');
            $workProcessSheet->setCellValue('A2', 'Chi nhánh: ' . $branch . '     Từ ngày: ' . $fromDate . ' đến ngày: ' . $toDate);
            $workProcessSheet->getStyle('A2')->getAlignment()->setHorizontal('right');
            $workProcessSheet->getStyle('A2')->getFont()->setBold(true);
            $workProcessSheet->getRowDimension(2)->setRowHeight(20);
            
            // Ghi chú giải thích
            $workProcessSheet->mergeCells('A3:L3');
            $workProcessSheet->setCellValue('A3', 'Ghi chú: - "% so với CN" = Phần trăm số ca nhân viên tiếp nhận so với tổng số ca của chi nhánh');
            $workProcessSheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
            $workProcessSheet->getStyle('A3')->getAlignment()->setHorizontal('left')->setVertical('center');
            $workProcessSheet->getRowDimension(3)->setRowHeight(18);
            
            $workProcessSheet->mergeCells('A4:L4');
            $workProcessSheet->setCellValue('A4', '        - Các phần trăm khác (%) = Phần trăm số ca của từng trạng thái so với tổng số ca nhân viên đó tiếp nhận');
            $workProcessSheet->getStyle('A4')->getFont()->setItalic(true)->setSize(10);
            $workProcessSheet->getStyle('A4')->getAlignment()->setHorizontal('left')->setVertical('center');
            $workProcessSheet->getRowDimension(4)->setRowHeight(18);
            
            // Bảng header
            $workProcessSheet->fromArray([
                ['STT', 'Chi nhánh', 'Tên kỹ thuật viên', 'Tổng tiếp nhận', '% so với CN', 'Đang sửa chữa', 'Đang sửa chữa %', 'Chờ KH phản hồi', 'Chờ KH phản hồi %', 'Đã hoàn tất', 'Đã hoàn tất %', 'Tỉ lệ trễ ca bảo hành (%)']
            ], NULL, 'A5');
            $workProcessSheet->getStyle('A5:L5')->getFont()->setBold(true);
            $workProcessSheet->getStyle('A5:L5')->getAlignment()->setVertical('center')->setHorizontal('center');
            $workProcessSheet->getStyle('A5:L5')->getFill()->setFillType(Fill::FILL_SOLID) 
                ->getStartColor()->setARGB('FFD3D3D3');
            $workProcessSheet->getStyle('A5:L5')->getBorders()->getAllBorders()->setBorderStyle('thin');
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
                
                $workProcessSheet->setCellValue('J' . $workRow, $item->da_hoan_tat ?? 0);
                $workProcessSheet->getStyle('J' . $workRow)->getAlignment()->setHorizontal('center');
                $workProcessSheet->setCellValue('K' . $workRow, ($item->da_hoan_tat_percent ?? 0) / 100);
                $workProcessSheet->getStyle('K' . $workRow)->getNumberFormat()->setFormatCode('0.00%');
                $workProcessSheet->getStyle('K' . $workRow)->getAlignment()->setHorizontal('center');

                $workProcessSheet->setCellValue('L' . $workRow, ($item->ti_le_qua_han ?? 0) / 100);
                $workProcessSheet->getStyle('L' . $workRow)->getNumberFormat()->setFormatCode('0.00%');
                $workProcessSheet->getStyle('L' . $workRow)->getAlignment()->setHorizontal('center');
                
                // Thêm border cho từng dòng
                $workProcessSheet->getStyle('A' . $workRow . ':L' . $workRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                
                $workRow++;
            }
            
            // Auto column width và format
            foreach (range('A', 'L') as $col) {
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
        
        // Tạo sheet mới: Tổng hợp bảo hành
        $productStats = $this->getProductWarrantyStats($request, $fromDateRaw, $toDateRaw);
        if ($productStats->isNotEmpty()) {
            $productSheet = $spreadsheet->createSheet();
            $productSheet->setTitle('Tổng hợp bảo hành');
            
            // Tiêu đề chính
            $productSheet->mergeCells('A1:F1');
            $productSheet->setCellValue('A1', 'BÁO CÁO TỔNG HỢP BẢO HÀNH THEO SẢN PHẨM');
            $productSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $productSheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
            $productSheet->getRowDimension(1)->setRowHeight(25);
            
            // Thông tin filter
            $productSheet->mergeCells('A2:F2');
            $productSheet->setCellValue('A2', 'Chi nhánh: ' . $branch . '     Từ ngày: ' . $fromDate . ' đến ngày: ' . $toDate);
            $productSheet->getStyle('A2')->getAlignment()->setHorizontal('right');
            $productSheet->getStyle('A2')->getFont()->setBold(true);
            $productSheet->getRowDimension(2)->setRowHeight(20);
            
            $currentRow = 3;
            
            // Duyệt qua từng danh mục
            foreach ($productStats as $categoryData) {
                // Header danh mục
                $productSheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
                $productSheet->setCellValue('A' . $currentRow, $categoryData['category_name']);
                $productSheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
                $productSheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');
                $productSheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                $productSheet->getRowDimension($currentRow)->setRowHeight(20);
                $currentRow++;
                
                // Header bảng
                $productSheet->fromArray([
                    ['TT', 'Tên sản phẩm', 'Tổng số TRƯỜNG HỢP LỖI', 'Bảo hành', 'Hết bảo hành', 'Tổng số tiền thu khách']
                ], NULL, 'A' . $currentRow);
                $productSheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getFont()->setBold(true);
                $productSheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getAlignment()->setVertical('center')->setHorizontal('center');
                $productSheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD3D3D3');
                $productSheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                $productSheet->getRowDimension($currentRow)->setRowHeight(25);
                $currentRow++;
                
                // Dữ liệu sản phẩm
                $stt = 1;
                foreach ($categoryData['products'] as $product) {
                    $productSheet->setCellValue('A' . $currentRow, $stt++);
                    $productSheet->setCellValue('B' . $currentRow, $product['product_name']);
                    $productSheet->setCellValue('C' . $currentRow, $product['tong_so_loi']);
                    $productSheet->setCellValue('D' . $currentRow, $product['bao_hanh']);
                    $productSheet->setCellValue('E' . $currentRow, $product['het_bao_hanh']);
                    $productSheet->setCellValue('F' . $currentRow, $product['tong_tien']);
                    
                    // Format số tiền
                    $productSheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
                    
                    // Căn giữa các cột số
                    $productSheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal('center');
                    $productSheet->getStyle('C' . $currentRow . ':E' . $currentRow)->getAlignment()->setHorizontal('center');
                    $productSheet->getStyle('F' . $currentRow)->getAlignment()->setHorizontal('right');
                    
                    // Border
                    $productSheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                    
                    $currentRow++;
                }
                
                // Tổng của danh mục
                $productSheet->setCellValue('A' . $currentRow, 'Tổng');
                $productSheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
                $productSheet->setCellValue('C' . $currentRow, $categoryData['total_loi']);
                $productSheet->setCellValue('D' . $currentRow, $categoryData['total_bao_hanh']);
                $productSheet->setCellValue('E' . $currentRow, $categoryData['total_het_bao_hanh']);
                $productSheet->setCellValue('F' . $currentRow, $categoryData['total_tien']);
                
                // Format tổng
                $productSheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
                $productSheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getFont()->setBold(true);
                $productSheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFE0');
                $productSheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                $productSheet->getStyle('C' . $currentRow . ':E' . $currentRow)->getAlignment()->setHorizontal('center');
                $productSheet->getStyle('F' . $currentRow)->getAlignment()->setHorizontal('right');
                
                $currentRow += 2; // Khoảng cách giữa các danh mục
            }
            
            // Auto column width
            foreach (range('A', 'F') as $col) {
                $productSheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Wrap text cho cột tên sản phẩm
            $productSheet->getStyle('B3:B' . ($currentRow - 1))->getAlignment()->setWrapText(true);
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
     * Đọc từ storage/app/reports/ (private storage)
     */
    public function viewReportPdf($filename)
    {
        $filename = basename($filename);
        
        // Nếu filename không có extension .pdf, thêm vào
        if (!str_ends_with($filename, '.pdf')) {
            $filename .= '.pdf';
        }

        // Đọc từ storage/app/reports/ (private, không phải public)
        $filePath = storage_path('app/reports/' . $filename);

        if (!file_exists($filePath)) {
            Log::error("PDF file not found", [
                'filename' => $filename,
                'filePath' => $filePath,
            ]);
            abort(404, 'File không tồn tại: ' . $filename);
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Lấy thống kê sản phẩm theo danh mục
     * - Tổng số TRƯỜNG HỢP LỖI
     * - Bảo hành (còn hạn)
     * - Hết bảo hành
     * - Tổng số tiền thu khách
     */
    private function getProductWarrantyStats(Request $request, $fromDate, $toDate)
    {
        $filterFromDate = Carbon::parse($fromDate)->startOfDay();
        $filterToDate = Carbon::parse($toDate)->endOfDay();
        
        // Xác định view: 1 = kuchen, 3 = hurom
        $view = session('brand') === 'hurom' ? 3 : 1;
        
        // Filter theo branch nếu có
        $branchFilter = null;
        if ($request->branch && $request->branch !== 'all' && $request->branch !== '') {
            $branchFilter = $request->branch;
        }
        
        // Lấy dữ liệu warranty requests với details
        $warrantyQuery = WarrantyRequest::query()
            ->whereBetween('received_date', [$filterFromDate, $filterToDate])
            ->where('status', 'Đã hoàn tất')
            ->where('view', $view);
        
        if ($branchFilter) {
            $warrantyQuery->where('branch', 'LIKE', '%' . $branchFilter . '%');
        }
        
        $warrantyRequests = $warrantyQuery->get();
        
        // Lấy danh sách sản phẩm có trong warranty requests và có category
        $productNames = $warrantyRequests->pluck('product')->filter()->unique()->map(function ($name) {
            return strtolower(trim($name));
        })->toArray();
        
        if (empty($productNames)) {
            return collect([]);
        }
        
        // Lấy thông tin sản phẩm từ bảng products với category_id
        $products = Product::query()
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'products.id',
                'products.product_name',
                'products.category_id',
                'categories.name_vi as category_name'
            )
            ->where('products.view', $view)
            ->whereIn(DB::raw('LOWER(TRIM(products.product_name))'), $productNames)
            ->get();
        
        // Lấy tất cả các categories có website_id = 2 từ database (không hardcode ID)
        $categoriesData = Category::where('website_id', 2)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        
        $categories = $categoriesData->pluck('name_vi', 'id')->toArray();
        $validCategoryIds = $categoriesData->pluck('id')->toArray();
        
        // Tạo categoryOrder động từ sort_order trong database
        $categoryOrder = [];
        $orderIndex = 1;
        foreach ($categoriesData as $category) {
            $categoryOrder[$category->id] = $orderIndex++;
        }
        
        // Tạo map product_name -> product info
        $productMap = [];
        foreach ($products as $product) {
            $key = strtolower(trim($product->product_name));
            $productMap[$key] = $product;
        }
        
        // Thêm các sản phẩm không có trong bảng products nhưng có trong warranty
        foreach ($productNames as $productName) {
            if (!isset($productMap[$productName])) {
                $productMap[$productName] = (object)[
                    'id' => null,
                    'product_name' => ucfirst($productName),
                    'category_id' => null,
                    'category_name' => null
                ];
            }
        }
        
        // Tính toán thống kê cho từng sản phẩm
        $productStats = [];
        foreach ($productMap as $key => $product) {
            $productWarranties = $warrantyRequests->filter(function ($wr) use ($key) {
                return strtolower(trim($wr->product)) === $key;
            });
            
            if ($productWarranties->isEmpty()) {
                continue;
            }
            
            $tongSoLoi = $productWarranties->count();
            $baoHanh = 0;
            $hetBaoHanh = 0;
            $tongTien = 0;
            
            foreach ($productWarranties as $warranty) {
                $receivedDate = Carbon::parse($warranty->received_date);
                $warrantyEnd = $warranty->warranty_end ? Carbon::parse($warranty->warranty_end) : null;
                
                if ($warrantyEnd && $warrantyEnd >= $receivedDate) {
                    $baoHanh++;
                } else {
                    $hetBaoHanh++;
                }
                
                // Lấy tổng tiền từ warranty_request_details
                $details = DB::table('warranty_request_details')
                    ->where('warranty_request_id', $warranty->id)
                    ->sum('total');
                
                $tongTien += $details ?? 0;
            }
            
            // Phân loại sản phẩm dựa trên category_id
            $categoryId = $product->category_id ?? null;
            
            // Xác định category_name dựa trên category_id
            if ($categoryId && in_array($categoryId, $validCategoryIds) && isset($categories[$categoryId])) {
                $categoryName = $categories[$categoryId];
            } else {
                // Sản phẩm không có category_id hoặc không thuộc các category hợp lệ -> "Thiết bị gia dụng"
                $categoryName = 'Thiết bị gia dụng';
            }
            
            $productStats[] = [
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'tong_so_loi' => $tongSoLoi,
                'bao_hanh' => $baoHanh,
                'het_bao_hanh' => $hetBaoHanh,
                'tong_tien' => $tongTien,
            ];
        }
        
        // Nhóm theo danh mục với thứ tự ưu tiên theo sort_order từ database
        // Thiết bị gia dụng (null) sẽ được đặt cuối cùng
        $maxOrder = count($categoryOrder) > 0 ? max($categoryOrder) : 0;
        $categoryOrder[null] = $maxOrder + 1; // Thiết bị gia dụng
        
        $groupedByCategory = collect($productStats)->groupBy('category_name')->map(function ($items, $categoryName) {
            // Lấy category_id từ item đầu tiên trong nhóm
            $categoryId = $items->first()['category_id'] ?? null;
            
            return [
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'products' => $items->sortBy('product_name')->values()->all(),
                'total_loi' => $items->sum('tong_so_loi'),
                'total_bao_hanh' => $items->sum('bao_hanh'),
                'total_het_bao_hanh' => $items->sum('het_bao_hanh'),
                'total_tien' => $items->sum('tong_tien'),
            ];
        })->sortBy(function ($item) use ($categoryOrder) {
            $categoryId = $item['category_id'] ?? null;
            return $categoryOrder[$categoryId] ?? 999;
        })->values();
        
        return $groupedByCategory;
    }

    /**
     * Preview báo cáo thống kê sản phẩm
     */
    public function previewProductWarrantyReport(Request $request)
    {
        $fromDate = $request->fromDate ?? Carbon::now()->startOfMonth()->toDateString();
        $toDate = $request->toDate ?? Carbon::today()->toDateString();
        
        $productStats = $this->getProductWarrantyStats($request, $fromDate, $toDate);
        
        $branchMap = [
            'kchanoi' => 'KUCHEN HÀ NỘI',
            'kcvinh' => 'KUCHEN VINH',
            'kchcm' => 'KUCHEN HCM',
            'hrhanoi' => 'HUROM HÀ NỘI',
            'hrvinh' => 'HUROM VINH',
            'hrhcm' => 'HUROM HCM',
        ];
        $branch = $branchMap[$request->query("branch", "")] ?? "Tất cả chi nhánh";
        $fromDateFormatted = Carbon::parse($fromDate)->format('d/m/Y');
        $toDateFormatted = Carbon::parse($toDate)->format('d/m/Y');
        
        return view('report.preview_product_warranty', [
            'productStats' => $productStats,
            'branch' => $branch,
            'fromDate' => $fromDateFormatted,
            'toDate' => $toDateFormatted,
        ]);
    }

    /**
     * Preview báo cáo Excel với 3 sheet
     */
    public function previewReportExcel(Request $request)
    {
        $fromDate = $request->fromDate ?? Carbon::now()->startOfMonth()->toDateString();
        $toDate = $request->toDate ?? Carbon::today()->toDateString();
        
        // Lấy dữ liệu từ session hoặc tính lại
        $data = session('dataExport');
        $workProcessData = session('workProcessDataExport');
        
        // Nếu không có trong session, tính lại
        if (!$data || $data->isEmpty()) {
            $parts = explode(' ', session('zone'));
            $zoneWithoutFirst = implode(' ', array_slice($parts, 1));
            $brand = strtoupper(session('brand')) . ' ' . $zoneWithoutFirst;
            if(session('position') == 'admin' || session('position') == 'quản trị viên'){
                $brand = '';
            }
            
            $view = session('brand') === 'hurom' ? 3 : 1;
            
            $conditions = [
                'warranty_requests.status' => 'Đã hoàn tất',
                ['warranty_requests.received_date', '>=', $fromDate],
                ['warranty_requests.received_date', '<=', $toDate],
                ['warranty_requests.view', '=', $view],
            ];
            
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
            
            if ($request->branch && $request->branch !== 'all' && $request->branch !== '') {
                $conditions[] = ['warranty_requests.branch', 'LIKE', '%' . $request->branch . '%'];
            }
            
            if ($request->staff_received) {
                $conditions[] = ['warranty_requests.staff_received', 'LIKE', '%' . $request->staff_received . '%'];
            }
            
            if ($request->solution && $request->solution !== 'tatca') {
                $conditions[] = ['warranty_request_details.solution', 'LIKE', '%' . $request->solution . '%'];
            }
            
            $data = WarrantyRequest::getListWarranty($conditions, $productInput);
            $workProcessData = $this->getWorkProcessStats($request, $brand, $fromDate, $toDate);
        }
        
        // Chuẩn bị dữ liệu cho Sheet 1: Báo cáo thống kê trường hợp bảo hành
        $sheet1Data = [];
        $stt = 1;
        $countBH = 0;
        $countLKBH = $priceLKBH = $PhiBH = 0;
        $countLKKBH = $priceLKKBH = $PhiHBH = 0;
        
        foreach ($data as $item) {
            $BH = 'Hết hạn BH';
            if($item->warranty_end >= $item->received_date){
                $BH = 'Còn hạn BH';
                $countBH++;
                if($item->replacement){
                    $countLKBH++;
                    $priceLKBH += $item->replacement_price * $item->quantity;
                    $PhiBH += $item->total;
                }
            } else {
                if($item->replacement){
                    $countLKKBH++;
                    $priceLKKBH += $item->replacement_price * $item->quantity;
                    $PhiHBH += $item->total;
                }
            }
            
            $sheet1Data[] = [
                'stt' => $stt++,
                'serial_number' => $item->serial_number,
                'serial_thanmay' => $item->serial_thanmay,
                'product' => $item->product,
                'branch' => $item->branch,
                'full_name' => $item->full_name,
                'phone_number' => $item->phone_number,
                'staff_received' => $item->staff_received,
                'received_date' => Carbon::parse($item->received_date)->format('d/m/Y'),
                'initial_fault_condition' => $item->initial_fault_condition,
                'shipment_date' => Carbon::parse($item->shipment_date)->format('d/m/Y'),
                'BH' => $BH,
                'replacement' => $item->replacement,
                'replacement_price' => $item->replacement_price,
                'quantity' => $item->quantity,
                'total_price' => $item->quantity * $item->replacement_price,
                'total' => $item->total
            ];
        }
        
        $sheet1Summary = [
            'con_bao_hanh' => ['label' => 'Còn bảo hành', 'count' => $countBH, 'linh_kien' => $countLKBH, 'chi_phi_linh_kien' => $priceLKBH, 'chi_phi_bao_hanh' => $PhiBH],
            'het_bao_hanh' => ['label' => 'Hết bảo hành', 'count' => $data->count() - $countBH, 'linh_kien' => $countLKKBH, 'chi_phi_linh_kien' => $priceLKKBH, 'chi_phi_bao_hanh' => $PhiHBH],
            'tong' => ['label' => 'Tổng', 'count' => $data->count(), 'linh_kien' => $countLKBH + $countLKKBH, 'chi_phi_linh_kien' => $priceLKBH + $priceLKKBH, 'chi_phi_bao_hanh' => $PhiBH + $PhiHBH],
        ];
        
        // Chuẩn bị dữ liệu cho Sheet 2: Quá trình làm việc
        $sheet2Data = [];
        if ($workProcessData && $workProcessData->isNotEmpty()) {
            $workBranchTotals = $workProcessData->groupBy('branch')->map(function ($branchStats) {
                return $branchStats->sum('tong_tiep_nhan');
            });
            
            $workProcessDataFormatted = $workProcessData->map(function ($item) use ($workBranchTotals) {
                $tongTiepNhan = $item->tong_tiep_nhan ?? 0;
                $branchTotal = $workBranchTotals->get($item->branch, 0);
                
                $item->phan_tram_chi_nhanh = $branchTotal > 0
                    ? ($tongTiepNhan / $branchTotal) * 100
                    : 0;
                
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
            
            $stt = 1;
            foreach ($workProcessDataFormatted as $item) {
                $sheet2Data[] = [
                    'stt' => $stt++,
                    'branch' => $item->branch ?? 'N/A',
                    'staff_received' => $item->staff_received ?? 'N/A',
                    'tong_tiep_nhan' => $item->tong_tiep_nhan ?? 0,
                    'phan_tram_chi_nhanh' => $item->phan_tram_chi_nhanh ?? 0,
                    'dang_sua_chua' => $item->dang_sua_chua ?? 0,
                    'dang_sua_chua_percent' => $item->dang_sua_chua_percent ?? 0,
                    'cho_khach_hang_phan_hoi' => $item->cho_khach_hang_phan_hoi ?? 0,
                    'cho_khach_hang_phan_hoi_percent' => $item->cho_khach_hang_phan_hoi_percent ?? 0,
                    'da_hoan_tat' => $item->da_hoan_tat ?? 0,
                    'da_hoan_tat_percent' => $item->da_hoan_tat_percent ?? 0,
                    'ti_le_qua_han' => $item->ti_le_qua_han ?? 0,
                ];
            }
            
            // Tính tổng tiếp nhận theo chi nhánh
            $sheet2BranchTotals = $workBranchTotals->toArray();
        } else {
            $sheet2BranchTotals = [];
        }
        
        // Chuẩn bị dữ liệu cho Sheet 3: Tổng hợp bảo hành
        $productStats = $this->getProductWarrantyStats($request, $fromDate, $toDate);
        
        $branchMap = [
            'kchanoi' => 'KUCHEN HÀ NỘI',
            'kcvinh' => 'KUCHEN VINH',
            'kchcm' => 'KUCHEN HCM',
            'hrhanoi' => 'HUROM HÀ NỘI',
            'hrvinh' => 'HUROM VINH',
            'hrhcm' => 'HUROM HCM',
        ];
        $branch = $branchMap[$request->query("branch", "")] ?? "Tất cả chi nhánh";
        $fromDateFormatted = Carbon::parse($fromDate)->format('d/m/Y');
        $toDateFormatted = Carbon::parse($toDate)->format('d/m/Y');
        
        return view('report.preview_excel', compact(
            'sheet1Data',
            'sheet1Summary',
            'sheet2Data',
            'sheet2BranchTotals',
            'productStats',
            'branch',
            'fromDateFormatted',
            'toDateFormatted'
        ));
    }

    /**
     * Phân loại sản phẩm theo trường name trong bảng products
     * @param string $productName Trường name từ bảng products
     * @return string Tên danh mục
     */
    private function getCategoryFromProductName($productName)
    {
        if (empty($productName)) {
            return 'Thiết bị gia dụng';
        }
        
        $name = strtolower(trim($productName));
        
        // Bếp điện từ
        if (strpos($name, 'bếp điện từ') !== false || 
            strpos($name, 'bep dien tu') !== false ||
            strpos($name, 'bếp từ') !== false ||
            strpos($name, 'bep tu') !== false) {
            return 'Bếp điện từ';
        }
        
        // Máy lọc không khí
        if (strpos($name, 'máy lọc không khí') !== false || 
            strpos($name, 'may loc khong khi') !== false ||
            strpos($name, 'máy lọc khí') !== false ||
            strpos($name, 'may loc khi') !== false ||
            strpos($name, 'air purifier') !== false ||
            strpos($name, 'airpurifier') !== false) {
            return 'Máy lọc không khí';
        }
        
        // Máy hút hút mùi
        if (strpos($name, 'máy hút hút mùi') !== false || 
            strpos($name, 'may hut hut mui') !== false ||
            strpos($name, 'máy hút mùi') !== false ||
            strpos($name, 'may hut mui') !== false ||
            strpos($name, 'hood') !== false ||
            strpos($name, 'hút mùi') !== false ||
            strpos($name, 'hut mui') !== false) {
            return 'Máy hút hút mùi';
        }
        
        // Máy rửa chén
        if (strpos($name, 'máy rửa chén') !== false || 
            strpos($name, 'may rua chen') !== false ||
            strpos($name, 'máy rửa bát') !== false ||
            strpos($name, 'may rua bat') !== false ||
            strpos($name, 'dishwasher') !== false ||
            strpos($name, 'dish washer') !== false) {
            return 'Máy rửa chén';
        }
        
        // Máy giặt, sấy
        if (strpos($name, 'máy giặt') !== false || 
            strpos($name, 'may giat') !== false ||
            strpos($name, 'máy sấy') !== false ||
            strpos($name, 'may say') !== false ||
            strpos($name, 'washer') !== false ||
            strpos($name, 'dryer') !== false ||
            strpos($name, 'washing machine') !== false ||
            strpos($name, 'drying machine') !== false) {
            return 'Máy giặt, sấy';
        }
        
        // Mặc định: Thiết bị gia dụng
        return 'Thiết bị gia dụng';
    }

}
