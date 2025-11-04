<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kho\Product;
use App\Models\Kho\WarrantyCard;
use App\Models\Kho\WarrantyActive;
use App\Models\Kho\SerialNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Pagination\Paginator;
use PhpOffice\PhpSpreadsheet\IOFactory;

Paginator::useBootstrap();

class PrintWarrantyController extends Controller
{
    static $pageSize = 50;
    
    public function __construct()
    {
        $this->middleware('permission:Xem danh sách tem')->only(['Index']);
        $this->middleware('permission:Thống kê số lượng kích hoạt bảo hành')->only(['ExportActiveWarranty']);
    }
    
    public function Index()
    {
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $toDay = Carbon::today()->toDateString();
        $products = [];
        $view = Session('brand') === 'hurom' ? 3 : 1;
        $lstWarrantyCard = WarrantyCard::query()
            ->where('view', $view)
            ->orderByDesc('create_at')
            ->paginate(self::$pageSize);
        $products = Product::where('view', $view)->select('id', 'view', 'product_name')->get()->toArray();
        return view("printwarranty.index", compact('lstWarrantyCard', 'products', 'startOfMonth', 'toDay'));
    }

    public function search(Request $request)
    {
        $view = Session('brand') === 'hurom' ? 3 : 1;
        $sophieu = $request->input('sophieu');
        $tensp = $request->input('tensp');
        $tungay = $request->input('tungay');
        $denngay = $request->input('denngay');
        $query = WarrantyCard::query()->where('view', $view);
        if (!empty($sophieu)) {
            $query->where('id', $sophieu);
        }
        if (!empty($tensp)) {
            $query->where('product', 'LIKE', '%' . $tensp . '%');
        }
        if (!empty($tungay)) {
            $query->whereDate('create_at', '>=', $tungay);
        }

        if (!empty($denngay)) {
            $query->whereDate('create_at', '<=', $denngay);
        }
        $lstWarrantyCard = $query->orderByDesc('create_at')->paginate(self::$pageSize);
        return view('printwarranty.tablebody', compact('lstWarrantyCard'));
    }

    public function Create(Request $request)
    {
        $view = Session('brand') === 'hurom' ? 3 : 1;
        $seriData = [];
        $serialsToInsert = [];
        
        // Kiểm tra sản phẩm có tồn tại không
        $product = Product::where('id', $request->product_id)
            ->where('view', $view)
            ->where('product_name', $request->product)
            ->first();
            
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại hoặc không hợp lệ. Vui lòng chọn lại sản phẩm.'
            ]);
        }
        
        if($request->serial_option == '0'){
            $card = WarrantyCard::create([
                'product' => $request->product,
                'product_id' => $request->product_id,
                'view' => $view,
                'type' => 0, // tự sinh mã serial
                'quantity' => $request->quantity,
                'create_by' => session('user'),
                'create_at' => now(),
            ]);
            
            $datePart = Carbon::now()->format('dmy');
            $startSerial = str_pad($card->id . $datePart, 13, '0', STR_PAD_RIGHT);
            for ($i = 1; $i <= $request->quantity; $i++) {
                $sn = str_pad($startSerial + $i, 10, '0', STR_PAD_LEFT);
                $serialsToInsert[] = $sn;
                $seriData[] = [
                    'sn' => $sn,
                    'product_id' => $request->product_id,
                    'product_name' => $request->product,
                    'manhaphang' => $card->id,
                ];
            }
        }
        
        if($request->serial_option == '1'){
            $serial_range = $request->serial_range;
            $duplicatesInInput = [];
            $seenSerials = [];

            $parts = explode(',', $serial_range);

            foreach ($parts as $part) {
                $part = trim($part);

                if (strpos($part, '-') !== false) {
                    [$start, $end] = explode('-', $part);
                    $start = trim($start);
                    $end = trim($end);

                    preg_match('/^([A-Za-z]*)(\d+)$/', $start, $matches_start);
                    preg_match('/^([A-Za-z]*)(\d+)$/', $end, $matches_end);

                    $prefix = isset($matches_start[1]) ? $matches_start[1] : '';
                    $prefix_end = isset($matches_end[1]) ? $matches_end[1] : '';

                    if ($prefix === $prefix_end && isset($matches_start[2], $matches_end[2])) {
                        $startNumber = (int)$matches_start[2];
                        $endNumber = (int)$matches_end[2];
                        $length = strlen($matches_start[2]);

                        for ($i = $startNumber; $i <= $endNumber; $i++) {
                            $sn = $prefix . str_pad($i, $length, '0', STR_PAD_LEFT);
                            
                            // Kiểm tra các serial trùng lặp
                            if (in_array($sn, $seenSerials)) {
                                $duplicatesInInput[] = $sn;
                            } else {
                                $seenSerials[] = $sn;
                                $serialsToInsert[] = $sn;
                                $seriData[] = [
                                    'sn' => $sn,
                                    'product_id' => $request->product_id,
                                    'product_name' => $request->product,
                                    'view' => $view,
                                    'manhaphang' => null, // sẽ cập nhật sau khi tạo card
                                ];
                            }
                        }
                    }
                } else {
                    $sn = trim($part);
                    
                    // Kiểm tra các bản sao trong đầu vào
                    if (in_array($sn, $seenSerials)) {
                        $duplicatesInInput[] = $sn;
                    } else {
                        $seenSerials[] = $sn;
                        $serialsToInsert[] = $sn;
                        $seriData[] = [
                            'sn' => $sn,
                            'product_id' => $request->product_id,
                            'product_name' => $request->product,
                            'view' => $view,
                            'manhaphang' => null, // sẽ cập nhật sau khi tạo card
                        ];
                    }
                }
            }

            // Kiểm tra các bản sao trong đầu vào
            if (!empty($duplicatesInInput)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dải serial có serial trùng lặp: ' . implode(', ', array_unique($duplicatesInInput))
                ]);
            }

            // Kiểm tra xem có bất kỳ serial hợp lệ nào để chèn không
            if (empty($serialsToInsert)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có serial hợp lệ nào trong dải serial'
                ]);
            }

            $soluong = count($serialsToInsert);

            $card = WarrantyCard::create([
                'product' => $request->product,
                'product_id' => $request->product_id,
                'view' => $view,
                'type' => $request->serial_option,
                'quantity' => $soluong,
                'create_by' => session('user'),
                'create_at' => now(),
            ]);

            // Gán manhaphang = $card->id cho từng bản ghi
            foreach ($seriData as &$item) {
                $item['manhaphang'] = $card->id;
            }
        }
        
        if ($request->serial_option == '2') {
            $file = $request->file('serial_file')->getPathName();
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();

            $serialsToInsert = [];
            $seriData = [];
            $duplicatesInFile = [];
            $seenSerials = [];

            $highestRow = $sheet->getHighestRow();
            for ($row = 1; $row <= $highestRow; $row++) {
                $value = trim($sheet->getCell('A' . $row)->getValue());
                if (!empty($value)) {
                    // Kiểm tra các trùng lặp trong tệp
                    if (in_array($value, $seenSerials)) {
                        $duplicatesInFile[] = $value;
                    } else {
                        $seenSerials[] = $value;
                        $serialsToInsert[] = $value;
                        $seriData[] = [
                            'sn' => $value,
                            'product_id' => $request->product_id,
                            'product_name' => $request->product,
                            'view' => $view,
                            'manhaphang' => null,
                        ];
                    }
                }
            }

            // Kiểm tra các bản sao trong tệp Excel
            if (!empty($duplicatesInFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File Excel có serial trùng lặp: ' . implode(', ', array_unique($duplicatesInFile))
                ]);
            }

            // Kiểm tra xem có bất kỳ serial hợp lệ nào để chèn không
            if (empty($serialsToInsert)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File Excel không có serial hợp lệ nào'
                ]);
            }

            $card = WarrantyCard::create([
                'product' => $request->product,
                'product_id' => $request->product_id,
                'view' => $view,
                'type' => 1,
                'quantity' => count($serialsToInsert),
                'create_by' => session('user'),
                'create_at' => now(),
            ]);

            foreach ($seriData as &$item) {
                $item['manhaphang'] = $card->id;
            }
        }
        
        $existingSN = SerialNumber::whereIn('sn', $serialsToInsert)->pluck('sn')->toArray();
        
        if (!empty($existingSN)) {
            WarrantyCard::where('id', $card->id)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Các serial sau đã tồn tại: ' . implode(', ', $existingSN)
            ]);
        }
        else{
            SerialNumber::insert($seriData);
        }
        
        return response()->json(['success' => true, 'message' => 'Đã lưu thành công']);
    }

    public function partialTable()
    {
        $view = Session('brand') === 'hurom' ? 3 : 1;
        $lstWarrantyCard = WarrantyCard::query()->where('view', $view)->orderByDesc('create_at')->paginate(self::$pageSize);
        return view('printwarranty.tablebody', compact('lstWarrantyCard'));
    }

    public function Details(Request $request)
    {
        $item = WarrantyCard::GetWarrantyCardByID(($request->id));
        $data = SerialNumber::where('manhaphang', $request->id)->get();
        if($item->type == 1){
            return view('printwarranty.serialdetails', compact('data', 'item'));
        }
        return view('printwarranty.details', compact('item'));
    }
    
    public function SerialDetails(Request $request)
    {   
        $item = WarrantyCard::where('id', $request->maphieu)->first();
        $data = SerialNumber::where('manhaphang', $request->maphieu)->get();
        return view('printwarranty.serialdetails', compact('data', 'item'));
    }

    public function TemView($id)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $pdfPath = "pdfs/tem-bao-hanh-{$id}.pdf";
        $storagePath = storage_path("app/public/" . $pdfPath);
        if (file_exists($storagePath)) {
            return response()->json(['url' => asset("storage/{$pdfPath}")]);
        }
        $serials = SerialNumber::where('manhaphang', $id)->get();
        $hotline = '1900 8071';
        $urlBase  = 'http://kuchenvietnam.vn/kich-hoat-bao-hanh/index.php?serial=';
        $logoPath = public_path('imgs/logokuchen.png');
        if (session('brand') == 'hurom') {
            $urlBase  = 'https://baohanh.hurom-vietnam.vn/dkbh/index.php?serial=';
            $hotline = '1900 9056';
            $logoPath = public_path('imgs/hurom.webp');
        }
        $logoBase64 = base64_encode(file_get_contents($logoPath));
        foreach ($serials as $serial) {
            $fullUrl = $urlBase . urlencode($serial->sn);
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($fullUrl)
                ->size(100)
                ->margin(5)
                ->build();
            $serial->qrCodeBase64 = base64_encode($result->getString());
        }
        $pdf = PDF::loadView('printwarranty.tem', compact('serials', 'hotline', 'logoBase64'))
            ->setPaper('A3', 'portrait');
        $pdf->save($storagePath);
        return response()->json(['url' => asset("storage/{$pdfPath}")]);
    }
    
    public function TemDowload($id)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $pdfPath = "pdfs/tem-bao-hanh-{$id}.pdf";
        $storagePath = storage_path("app/public/" . $pdfPath);
        if (file_exists($storagePath)) {
            return response()->download($storagePath, "tem-bao-hanh-{$id}.pdf");
        }
        $serials = SerialNumber::where('manhaphang', $id)->get();
        $hotline = '1900 8071';
        $urlBase  = 'http://kuchenvietnam.vn/kich-hoat-bao-hanh/index.php?serial=';
        $logoPath = public_path('imgs/logokuchen.png');
        if (session('brand') == 'hurom') {
            $urlBase  = 'https://baohanh.hurom-vietnam.vn/dkbh/index.php?serial=';
            $hotline = '1900 9056';
            $logoPath = public_path('imgs/hurom.webp');
        }
        $logoBase64 = base64_encode(file_get_contents($logoPath));
        foreach ($serials as $serial) {
            $fullUrl = $urlBase . urlencode($serial->sn);
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($fullUrl)
                ->size(100)
                ->margin(5)
                ->build();
            $serial->qrCodeBase64 = base64_encode($result->getString());
        }
        $pdf = PDF::loadView('printwarranty.tem', compact('serials', 'hotline', 'logoBase64'))
            ->setPaper('A3', 'portrait');
        $pdf->save($storagePath);
        return response()->download($storagePath, "tem-bao-hanh-{$id}.pdf");
    }

    
    public function ExportActiveWarranty(Request $request)
    {
        $view = Session('brand') === 'hurom' ? 3 : 1;
        $startMonth = Carbon::now()->startOfMonth();
        $toDay = Carbon::now();
        $fromDate = $request->query('fromDate') ?? $startMonth;
        $toDate = $request->query('toDate') ?? $toDay;

        $data = WarrantyActive::query()->where('view', $view)->whereBetween('active_date', [$fromDate, Carbon::now()->endOfDay()])->get();
        if (!$data || $data->count() == 0) {
            return response()->json([
                'status' => "error",
                'message' => 'Không có dữ liệu!'
            ], 200);
        }
        $fromDateFormatted = Carbon::parse($fromDate)->format('d/m/Y');
        $toDateFormatted = Carbon::parse($toDate)->format('d/m/Y');
        // Tạo file Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A1', 'BÁO CÁO THỐNG KÊ CA KÍCH HOẠT BẢO HÀNH');
        $sheet->setCellValue('A2', 'Từ ngày: ' . $fromDateFormatted . ' - đến ngày: ' . $toDateFormatted);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->fromArray([['STT', 'TÊN SẢN PHẨM', 'SỐ LƯỢNG']], NULL, 'A3');
        $sheet->getStyle('A3:C3')->getFont()->setBold(true);
        $sheet->getStyle('A3:C3')->getAlignment()->setVertical('center');
        $productCounts = [];

        foreach ($data as $item) {
            $productName = $item->product;

            if (!isset($productCounts[$productName])) {
                $productCounts[$productName] = 1;
            } else {
                $productCounts[$productName]++;
            }
        }

        $row = 4;
        $stt = 1;
        foreach ($productCounts as $productName => $quantity) {
            $sheet->fromArray([
                $stt++,
                $productName,
                $quantity,
            ], NULL, "A{$row}");
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal('center');
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal('center');
            $row++;
        }
        $sheet->fromArray([
            '',
            'TỔNG CỘNG',
            $data->count()
        ], NULL, "A{$row}");
        // Làm nổi bật dòng tổng cộng (tùy chọn)
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:C{$row}")->getAlignment()->setHorizontal('center');

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

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
    
    public function Delete($id){
        $lstSerial = SerialNumber::where('manhaphang', $id)->get();
        if ($lstSerial->count() > 0) {
            SerialNumber::where('manhaphang', $id)->delete();
        }
        $card = WarrantyCard::find($id);
        if ($card) {
            $card->delete();
            return response()->json(['success' => true, 'message' => 'Xóa thành công!']);
        }
        return response()->json(['success' => false, 'message' => 'Không tìm thấy phiếu!']);
    }
}