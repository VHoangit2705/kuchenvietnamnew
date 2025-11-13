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
use App\Models\Kho\Product;
use Illuminate\Support\Str;


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
        if(session('position') == 'admin'){
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

        if ($request->branch && $request->branch !== 'all') {
            $conditions[] = ['warranty_requests.branch', 'LIKE', '%' . $request->branch . '%'];
        } else {
            $conditions[] = ['warranty_requests.branch', 'LIKE', '%' . $brand . '%'];
        }

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

        session(['dataExport' => $data]);
        return view('report.listwarranty', [
            'data' => $data,
            'fromDate' => Carbon::parse($fromDate),
            'toDate' => Carbon::parse($toDate),
            'userBranch' => $brand,
            'linhkien' => $linhkien,
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
}
