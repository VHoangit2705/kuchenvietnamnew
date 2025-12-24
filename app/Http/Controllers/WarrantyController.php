<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enum;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use App\Models\Kho\Product;
use App\Models\KyThuat\WarrantyRequestDetail;
use App\Models\KyThuat\WarrantyRequest;
use App\Models\KyThuat\WarrantyCollaborator;
use App\Models\KyThuat\WarrantyRequestDetailsHistory;
use App\Models\KyThuat\WarrantyRequestsHistory;
use Illuminate\Support\Arr;
use App\Models\KyThuat\TemBaoHanh;
use App\Models\KyThuat\Province;
use App\Models\KyThuat\KhachHang;
use App\Models\Kho\ProductWarranty;
use App\Models\Kho\WarrantyActive;
use App\Models\Kho\OrderProduct;
use App\Models\Kho\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\KyThuat\UserDeviceToken;
use App\Models\KyThuat\WarrantyAnomalyAlert;
use App\Models\KyThuat\WarrantyAnomalyBlock;
use App\Services\WarrantyAnomalyDetector;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\KyThuat\WarrantyUploadError;
use App\Models\KyThuat\WarrantyRepairJob;

Paginator::useBootstrap();

class WarrantyController extends Controller
{
    static $pageSize = 50;
    public function __construct()
    {
        //Home
        $this->middleware('permission:Kuchen')->only(['IndexKuchen']);
        $this->middleware('permission:Hurom')->only(['IndexHurom']);
        //Danh sách ca bảo hành
        $this->middleware('permission:Xem ca bảo hành')->only(['Details']);
        //Tiếp nhận ca bảo hành
        $this->middleware('permission:Tra cứu tiếp nhận')->only(['CheckWarranty']);
        $this->middleware('permission:Tạo phiếu tiếp nhận bảo hành')->only(['FormWarrantyCard']);
    }
    
    public function IndexKuchen()
    {
        session(['brand' => "kuchen"]);
        // $zone = explode(' ', session('zone'));
        // $userBranch = 'KUCHEN' . ' ' . end($zone);
        $parts = explode(' ', session('zone'));
        $zoneWithoutFirst = implode(' ', array_slice($parts, 1));
        $userBranch = strtoupper(session('brand')) . ' ' . $zoneWithoutFirst;
        $vitri = strtolower(session('position'));
         // Lấy ngày từ request, nếu không có thì mặc định 30 ngày trước
        $fromDate = Carbon::parse(request('fromDate', now()->subDays(30)));
        $toDate = Carbon::parse(request('toDate', now()));
        $branchMap = [
            'vinh' => 'kuchen vinh',
            'hcm' => 'kuchen hcm',
            'hà nội' => 'kuchen hanoi'
        ];

        $branchFilter = 'kuchen';
        if ($vitri === 'kỹ thuật viên') {
            foreach ($branchMap as $key => $val) {
                if (str_contains(strtolower($userBranch), $key)) {
                    $branchFilter = $val;
                    break;
                }
            }
        }

        $query = WarrantyRequest::query()
            ->where('branch', 'like', 'kuchen%')
            ->when($branchFilter !== 'hurom', function ($q) use ($branchFilter) {
                return $q->whereRaw('LOWER(branch) LIKE ?', ['%' . strtolower($branchFilter) . '%']);
            })
            ->when($sophieu = request('sophieu'), fn($q) => $q->where('id', 'like', "%$sophieu%"))
            ->when($seri = request('seri'), fn($q) => $q->where('serial_number', 'like', "%$seri%"))
            ->when($product_name = request('product_name'), fn($q) => $q->where('product', 'like', "%$product_name%"))
            ->when($sdt = request('sdt'), fn($q) => $q->where('phone_number', 'like', "%$sdt%"))
            ->when($khachhang = request('khachhang'), fn($q) => $q->where('full_name', 'like', "%$khachhang%"))
            ->when($kythuatvien = request('kythuatvien'), fn($q) => $q->where('staff_received', 'like', "%$kythuatvien%"))
            ->when($chinhanh = request('chinhanh'), fn($q) => $q->where('branch', 'like', "%$chinhanh%"))
            ->when($fromDate && $toDate, function ($q) use ($fromDate, $toDate) {
                return $q->whereBetween('received_date', [$fromDate->startOfDay(), $toDate->endOfDay()]);
            });

        $counts = (clone $query)
            ->selectRaw("
                COUNT(*) as danhsach,
                SUM(CASE WHEN status = 'Đang sửa chữa' THEN 1 ELSE 0 END) as dangsua,
                SUM(CASE WHEN status = 'Chờ KH phản hồi' THEN 1 ELSE 0 END) as chophanhoi,
                SUM(CASE WHEN status = 'Đã hoàn tất' THEN 1 ELSE 0 END) as hoantat,
                SUM(CASE WHEN status != 'Đã hoàn tất' AND status != 'Chờ KH phản hồi' AND return_date < ? THEN 1 ELSE 0 END) as quahan
            ", [now()])
            ->first()
            ->toArray();

        // Xử lý tab hiện tại
        $tab = request()->get('tab', 'danhsach');
        $tabQuery = clone $query;

        match ($tab) {
            'hoantat' => $tabQuery->where('status', 'Đã hoàn tất'),
            'dangsua' => $tabQuery->where('status', 'Đang sửa chữa'),
            'chophanhoi' => $tabQuery->where('status', 'Chờ KH phản hồi'),
            'quahan' => $tabQuery->whereDate('return_date', '<=', now())->where('status', 'Đang sửa chữa')->orderBy('id', 'asc'),
            default => null,
        };

        $data = $tabQuery->orderByDesc('received_date')->orderByDesc('id')->paginate(self::$pageSize)->withQueryString();
         $products = [];
        if (session('brand') == 'kuchen') {
            $products = Product::where('view', '1')->select('product_name')->get()->toArray();
        }
        if (request()->ajax()) {
            return response()->json([
                'tab' => view('components.tabheader', [
                    'counts' => $counts,
                    'activeTab' => $tab
                ])->render(),
                'table' => view('components.tabcontent', compact('data'))->render(),
            ]);
        }

        return view('warranty.homewarranty', compact('data', 'userBranch', 'counts', 'products', 'tab', 'fromDate', 'toDate'));
    }


    public function IndexHurom()
    {
        session(['brand' => "hurom"]);
        // $zone = explode(' ', session('zone'));
        // $userBranch = 'HUROM' . ' ' . end($zone);
        $parts = explode(' ', session('zone'));
        $zoneWithoutFirst = implode(' ', array_slice($parts, 1));
        $userBranch = strtoupper(session('brand')) . ' ' . $zoneWithoutFirst;
        $vitri = strtolower(session('position'));
        // $today = Carbon::today()->toDateString();
       // Lấy ngày từ request, nếu không có thì mặc định 30 ngày trước
        $fromDate = Carbon::parse(request('fromDate', now()->subDays(30)));
        $toDate = Carbon::parse(request('toDate', now()));
        $branchMap = [
            'vinh' => 'hurom vinh',
            'hcm' => 'hurom hcm',
            'hà nội' => 'hurom hanoi'
        ];

        // Mặc định là tất cả hurom
        $branchFilter = 'hurom';

        // Nếu là kỹ thuật viên thì lọc chi nhánh cụ thể
        if ($vitri === 'kỹ thuật viên') {
            foreach ($branchMap as $key => $val) {
                if (str_contains(strtolower($userBranch), $key)) {
                    $branchFilter = $val;
                    break;
                }
            }
        }

        $query = WarrantyRequest::query()
            ->where('branch', 'like', 'hurom%')
            ->when($branchFilter !== 'hurom', function ($q) use ($branchFilter) {
                return $q->whereRaw('LOWER(branch) LIKE ?', ['%' . strtolower($branchFilter) . '%']);
            })
            ->when($sophieu = request('sophieu'), fn($q) => $q->where('id', 'like', "%$sophieu%"))
            ->when($seri = request('seri'), fn($q) => $q->where('serial_number', 'like', "%$seri%"))
            ->when($product_name = request('product_name'), fn($q) => $q->where('product', 'like', "%$product_name%"))
            ->when($sdt = request('sdt'), fn($q) => $q->where('phone_number', 'like', "%$sdt%"))
            ->when($khachhang = request('khachhang'), fn($q) => $q->where('full_name', 'like', "%$khachhang%"))
            ->when($kythuatvien = request('kythuatvien'), fn($q) => $q->where('staff_received', 'like', "%$kythuatvien%"))
            ->when($chinhanh = request('chinhanh'), fn($q) => $q->where('branch', 'like', "%$chinhanh%"))
            ->when($fromDate && $toDate, function ($q) use ($fromDate, $toDate) {
                return $q->whereBetween('received_date', [$fromDate->startOfDay(), $toDate->endOfDay()]);
            });


        $counts = (clone $query)
            ->selectRaw("
                COUNT(*) as danhsach,
                SUM(CASE WHEN status = 'Đang sửa chữa' THEN 1 ELSE 0 END) as dangsua,
                SUM(CASE WHEN status = 'Chờ KH phản hồi' THEN 1 ELSE 0 END) as chophanhoi,
                SUM(CASE WHEN status = 'Đã hoàn tất' THEN 1 ELSE 0 END) as hoantat,
                SUM(CASE WHEN status != 'Đã hoàn tất' AND status != 'Chờ KH phản hồi' AND return_date < ? THEN 1 ELSE 0 END) as quahan
            ", [now()])
            ->first()
            ->toArray();

        // Xử lý tab hiện tại
        $tab = request()->get('tab', 'danhsach');
        $tabQuery = clone $query;

        match ($tab) {
            'hoantat' => $tabQuery->where('status', 'Đã hoàn tất'),
            'dangsua' => $tabQuery->where('status', 'Đang sửa chữa'),
            'chophanhoi' => $tabQuery->where('status', 'Chờ KH phản hồi'),
            'quahan' => $tabQuery->whereDate('return_date', '<=', now())->where('status', 'Đang sửa chữa')->orderBy('id', 'asc'),
            default => null,
        };

        $data = $tabQuery->orderByDesc('received_date')->orderByDesc('id')->paginate(self::$pageSize)->withQueryString();
        $products = [];
        if (session('brand') == 'hurom') {
            $products = Product::where('view', '3')->select('product_name')->get()->toArray();
        }
        if (request()->ajax()) {
            $tabHtml = view('components.tabheader', [
                'counts' => $counts,
                'activeTab' => $tab
            ])->render();

            $tableHtml = view('components.tabcontent', compact('data'))->render();

            return response()->json([
                'tab' => $tabHtml,
                'table' => $tableHtml,
            ]);
        }

        return view('warranty.homewarranty', compact('data', 'userBranch', 'counts', 'products', 'tab', 'fromDate', 'toDate'));
    }
    //phân trang
    public function paginateCollection(Collection $items, $perPage, $currentPage)
    {
        $currentPage = max(1, (int) $currentPage);
        $currentPageItems = $items->slice(($currentPage - 1) * $perPage, $perPage);
        return new LengthAwarePaginator(
            $currentPageItems,
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function ThongBaoBaoHanh()
    {
        $brand = session('brand');
        $full_name = session('user');
        $count = WarrantyRequest::where('branch', 'like', $brand . '%')
            ->where('staff_received', $full_name)
            ->whereDate('return_date', '<=', now())
            ->where('status', 'Đang sửa chữa')
            ->count();
        if($count < 1){
            return response()->json([
                'success' => false,
                'message' => 'Không có ca bảo hành nào quá hạn.'
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Bạn có ' . $count . ' ca bảo hành quá hạn, xin hãy kiểm tra.',
            'nhanvien' => session('user')
        ]);
    }

    public function search(Request $request)
    {
        $zone = explode(' ', session('zone'));
        if (session('brand') == 'kuchen') {
            $query = WarrantyRequest::whereRaw('LOWER(branch) LIKE "%kuchen%"')->orderBy('id', 'desc');
            $branches = ['KUCHEN VINH', 'KUCHEN HÀ NỘI', 'KUCHEN HCM'];
            $userBranch = 'KUCHEN' . ' ' . end($zone);
        } else {
            $query = WarrantyRequest::whereRaw('LOWER(branch) LIKE "%hurom%"')->orderBy('id', 'desc');
            $branches = ['HUROM VINH', 'HUROM HÀ NỘI', 'HUROM HCM'];
            $userBranch = 'HUROM' . ' ' . end($zone);
        }

        // Lọc các trường tìm kiếm
        if ($request->filled('sophieu')) {
            $query->where('id',  $request->sophieu);
        }

        if ($request->filled('seri')) {
            $query->where('serial_number', 'LIKE', '%' . $request->seri . '%');
        }

        if ($request->filled('sdt')) {
            $query->where('phone_number', 'LIKE', '%' . $request->sdt . '%');
        }

        if ($request->filled('khachhang')) {
            $query->where('full_name', 'LIKE', '%' . $request->khachhang . '%');
        }

        if ($request->filled('kythuatvien')) {
            $query->where('staff_received', 'LIKE', '%' . $request->kythuatvien . '%');
        }

        if ($request->filled('product')) {
            $query->where('product', 'LIKE', '%' . $request->product . '%');
        }

        // Thêm điều kiện chi nhánh nếu có
        if ($request->filled('chinhanh') && in_array($request->chinhanh, $branches)) {
            $query->where('branch', $request->chinhanh);
        }

        // Lấy số trang hiện tại
        $perPage = 30;
        $page = $request->get('page', 1);

        // Sử dụng clone để phân trang từng nhóm
        $data = (clone $query)->orderBy('received_date', 'desc')->paginate($perPage);
        $hoantat = (clone $query)->where('status', 'Đã hoàn tất')->paginate($perPage);
        $dangsua = (clone $query)->where('status', 'Đang sửa chữa')->paginate($perPage);
        $chophanhoi = (clone $query)->where('status', 'Chờ KH phản hồi')->paginate($perPage);
        $quahan = (clone $query)
            ->where('return_date', '<', now())
            ->where('status', '!=', 'Đã hoàn tất')
            ->paginate($perPage);

        return view("warranty.warranty", compact("data", "hoantat", "dangsua", "chophanhoi", "quahan", "userBranch"));
    }
    
    public function UpdateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:warranty_requests,id',
            'status' => 'required|string'
        ]);

        $quatrinh = WarrantyRequestDetail::getDetailsByRequestId($request->id);
        if ($quatrinh->isEmpty() && $request->status == 'Đã hoàn tất') {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa cập nhật quá trình bảo hành.'
            ]);
        }

        $wr = WarrantyRequest::find($request->id);
        if ((Empty($wr->image_upload) && empty($wr->video_upload)) && $request->status == 'Đã hoàn tất') {
            return response()->json([
                'success' => false,
                'message' => 'Bạn phải thêm ảnh hoặc video sản phẩm lỗi.'
            ]);
        }
        WarrantyRequest::find($request->id)->update(['status' => $request->status]);

        $lstComponent = $request->components;
        if ($lstComponent && count($lstComponent) > 0) {
            foreach ($lstComponent as $component) {
                if (!isset($component['id']) || !isset($component['return_quantity'])) {
                    continue;
                }
                $record = WarrantyRequestDetail::where('id', $component['id'])->first();

                if ($record) {
                    $newRecord = $record->replicate();
                    $newRecord->replacement = $record->replacement . ' (hàng trả về)';
                    $newRecord->quantity = $component['return_quantity'];
                    $newRecord->replacement_price = 0;
                    $newRecord->unit_price = 0;
                    $newRecord->total = 0;
                    $newRecord->save();
                }
            }
        }
        return response()->json(['success' => true, 'message' => 'Cập nhật thành công.']);
    }
    //lấy danh sách linh kiện
    public function GetComponents($sophieu)
    {
        $details = WarrantyRequestDetail::where('warranty_request_id', $sophieu)->select('id', 'replacement', 'quantity')->get();
        return response()->json($details);
    }
    //Chi tiết ca bảo hành
    public function Details($id)
    {
        // Eager load relationships để tránh N+1 queries
        $data = WarrantyRequest::with(['details', 'repairJobs'])
            ->where('id', $id)
            ->first();
        
        if (!$data) {
            abort(404, 'Không tìm thấy phiếu bảo hành');
        }

        // Lấy dữ liệu hình ảnh và video lỗi từ bảng warranty_upload_error
        // Cho phép nhiều bản ghi cho cùng một warranty_request_id (mỗi lần upload là một bản ghi)
        $errorRecords = WarrantyUploadError::where('warranty_request_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();
        if ($errorRecords->isNotEmpty()) {
            // Chuẩn hoá ghi chú: bỏ tiền tố thời gian cũ nếu có (dd/mm/YYYY HH:ii - ...)
            $normalizeNote = function (?string $note) {
                if ($note === null) {
                    return null;
                }
                $trimmed = trim($note);
                if ($trimmed === '') {
                    return null;
                }

                // Dạng cũ: "dd/mm/YYYY HH:ii - nội dung"
                $parts = preg_split('/\s*-\s*/', $trimmed, 2);
                if (
                    count($parts) === 2 &&
                    preg_match('/^\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}$/', $parts[0])
                ) {
                    return trim($parts[1]);
                }

                return $trimmed;
            };

            // Gom tất cả đường dẫn ảnh lỗi lại, phân tách bằng dấu phẩy
            $allErrorImages = $errorRecords
                ->pluck('image_upload_error')
                ->filter()
                ->implode(',');

            // Với video lỗi: lấy bản ghi mới nhất có video_upload_error
            $latestVideoError = $errorRecords
                ->whereNotNull('video_upload_error')
                ->sortByDesc('updated_at')
                ->first();

            // Ghi chú lỗi: nối tất cả note_error theo thứ tự thời gian
            $allNotes = $errorRecords
                ->pluck('note_error')
                ->map($normalizeNote)
                ->filter()
                ->implode(PHP_EOL);

            $data->image_upload_error = $allErrorImages;
            $data->video_upload_error = $latestVideoError?->video_upload_error;
            $data->note_error = $allNotes;

            // Danh sách các lần upload lỗi riêng lẻ (để hiển thị theo từng ghi chú)
            // Chỉ lấy những record có ảnh lỗi (không lấy record chỉ có video)
            $data->error_image_batches = $errorRecords
                ->filter(function ($record) {
                    return !empty($record->image_upload_error) && trim($record->image_upload_error) !== '';
                })
                ->map(function ($record) use ($normalizeNote) {
                    return (object) [
                        'id' => $record->id,
                        'images' => $record->image_upload_error,
                        'note' => $normalizeNote($record->note_error),
                        'created_at' => $record->created_at,
                    ];
                });
        }

        // Lấy details đã eager load và lưu vào biến để tránh query lại
        // Sử dụng getRelation() để đảm bảo lấy từ cache, không query lại
        // Nếu chưa có trong relation, sẽ lấy từ relationship (đã eager load)
        $detailsCollection = $data->relationLoaded('details')
            ? $data->getRelation('details')
            : $data->details;
        
        // Đảm bảo relationship được set để tránh query lại khi truy cập trong view
        if (!$data->relationLoaded('details')) {
            $data->setRelation('details', $detailsCollection);
        }
        
        // Sử dụng relationship đã eager load và sort trong memory (không query lại)
        $quatrinhsuaRaw = $detailsCollection
            ->sortBy([
                ['Ngaytao', 'asc'],
                ['id', 'asc']
            ])
            ->values();
        
        // Nhóm các bản ghi có cùng error_type, solution, và Ngaytao
        $quatrinhsua = collect();
        $grouped = $quatrinhsuaRaw->groupBy(function ($item) {
            return $item->error_type . '|' . $item->solution . '|' . $item->Ngaytao;
        });
        
        foreach ($grouped as $group) {
            $firstItem = $group->first();
            $components = $group->map(function ($item, $index) {
                return [
                    'number' => $index + 1,
                    'name' => $item->replacement,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->quantity * $item->unit_price
                ];
            });
            
            // Tính tổng số lượng và thành tiền
            $totalQuantity = $group->sum('quantity');
            $totalAmount = $group->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });
            
            // Kiểm tra xem có nhiều linh kiện với đơn giá khác nhau không
            $uniquePrices = $group->pluck('unit_price')->unique()->count();
            $hasMultiplePrices = $uniquePrices > 1 || $group->count() > 1;
            
            // Tạo object mới với thông tin đã nhóm
            $groupedItem = (object) [
                'id' => $firstItem->id, 
                'error_type' => $firstItem->error_type,
                'solution' => $firstItem->solution,
                'replacement' => $components,
                'quantity' => $totalQuantity,
                'unit_price' => $hasMultiplePrices ? null : ($group->first()->unit_price ?? 0),
                'total' => $totalAmount,
                'Ngaytao' => $firstItem->Ngaytao,
                'warranty_request_id' => $firstItem->warranty_request_id,
                'edit_by' => $firstItem->edit_by,
            ];
            
            $quatrinhsua->push($groupedItem);
        }
        
        // Eager load details cho history để tránh N+1 queries
        // Loại trừ warranty request hiện tại để tránh query lại details đã load
        $history = WarrantyRequest::with('details')
            ->where('serial_number', $data->serial_number)
            ->where('phone_number', $data->phone_number)
            ->where('product', $data->product)
            ->where('id', '!=', $data->id) // Loại trừ warranty request hiện tại
            ->orderBy('received_date', 'desc')
            ->get();
        
        // Thêm warranty request hiện tại vào history và sắp xếp lại theo received_date
        // Sử dụng details đã eager load sẵn, không query lại
        $history->push($data);
        $history = $history->sortByDesc('received_date')->values();
        
        // Lấy danh sách linh kiện
        $linhkien = Product::where('view', '2')
            ->select('product_name', 'view')
            ->get();
        
        // Lấy danh sách sản phẩm dựa trên brand
        $view = session('brand') === 'hurom' ? 3 : 1;
        $sanpham = Product::where('view', $view)
            ->select('product_name', 'view')
            ->get();
        
        $repairJobs = $data->repairJobs->sortBy('created_at')->values();
        $repairJobsTotal = $repairJobs->sum('total_price');
        
        // Truyền cả dữ liệu để có thể sử dụng khi edit
        return view('warranty.warrantydetails', compact(
            'data',
            'quatrinhsua',
            'quatrinhsuaRaw',
            'history',
            'linhkien',
            'sanpham',
            'repairJobs',
            'repairJobsTotal'
        ));
    }
    // cập nhật quá trình sửa chữa
    public function UpdateDetail(Request $request)
    {
        // Kiểm tra nếu replacement là mảng (nhiều linh kiện)
        $isMultipleComponents = false;
        if (is_array($request->replacement) && count($request->replacement) > 0) {
            $isMultipleComponents = true;
        }
        
        // Validation rules
        $rules = [
            'error_type' => 'required|string|max:255',
            'solution' => 'required|string|max:255',
            'des_error_type' => 'nullable',
            'customer_refusal_reason' => 'nullable|string|max:255',
        ];

        if ($isMultipleComponents) {
            // Validation cho nhiều linh kiện
            $rules['replacement'] = 'required|array';
            $rules['replacement.*'] = 'nullable|string|max:255';
            $rules['quantity'] = 'required|array';
            $rules['quantity.*'] = 'nullable|integer|min:0';
            $rules['unit_price'] = 'required|array';
            $rules['unit_price.*'] = 'nullable|integer|min:0';
        } else {
            // Validation cho một linh kiện (backward compatible)
            $rules['replacement'] = 'nullable|string|max:255';
            $rules['quantity'] = 'nullable|integer|min:0';
            $rules['unit_price'] = 'nullable|integer|min:0';
        }

        $validator = Validator::make($request->all(), $rules);

        // Kiểm tra bắt buộc linh kiện cho một số giải pháp
        if (($request->solution === 'Thay thế linh kiện/hardware' || $request->solution === 'Đổi mới sản phẩm')) {
            if ($isMultipleComponents) {
                $hasValidComponent = false;
                foreach ($request->replacement as $index => $replacement) {
                    if (!empty($replacement) && 
                        isset($request->quantity[$index]) && 
                        $request->quantity[$index] > 0) {
                        $hasValidComponent = true;
                        break;
                    }
                }
                if (!$hasValidComponent) {
                    $validator->after(function ($validator) use ($request) {
                        $fieldName = $request->solution === 'Đổi mới sản phẩm' ? 'Sản phẩm thay thế' : 'Linh kiện thay thế';
                        $validator->errors()->add('replacement.0', $fieldName . ' là bắt buộc khi chọn giải pháp này.');
                    });
                }
            } else {
                if (empty($request->replacement)) {
                    $validator->after(function ($validator) use ($request) {
                        $fieldName = $request->solution === 'Đổi mới sản phẩm' ? 'Sản phẩm thay thế' : 'Linh kiện thay thế';
                        $validator->errors()->add('replacement', $fieldName . ' là bắt buộc khi chọn giải pháp này.');
                    });
                }
            }
        }

        // Trường hợp khách hàng không muốn bảo hành -> bắt buộc nhập lý do
        if ($request->solution === 'KH không muốn bảo hành') {
            $validator->after(function ($validator) use ($request) {
                $reason = trim((string) $request->customer_refusal_reason);
                if ($reason === '') {
                    $validator->errors()->add(
                        'customer_refusal_reason',
                        'Lý do khách hàng không muốn bảo hành là bắt buộc.'
                    );
                }
            });
        }
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Xử lý khi edit (có id) - xóa các bản ghi cũ và tạo mới
        if ($request->id) {
            $oldDetail = WarrantyRequestDetail::find($request->id);
            if ($oldDetail) {
                WarrantyRequestDetail::where('warranty_request_id', $oldDetail->warranty_request_id)
                    ->where('error_type', $oldDetail->error_type)
                    ->where('solution', $oldDetail->solution)
                    ->where('Ngaytao', $oldDetail->Ngaytao)
                    ->delete();
            }
        }

        // Dữ liệu chung cho tất cả các bản ghi
        $commonData = [
            'warranty_request_id' => $request->warranty_request_id,
            'error_type' => $request->error_type,
            'solution' => $request->solution,
            'Ngaytao' => Carbon::now(),
            'edit_by' => session('user'),
        ];

        // Trường hợp khách hàng không muốn bảo hành:
        // Lưu lý do khách từ chối vào cột `replacement` của bảng warranty_request_details
        if ($request->solution === 'KH không muốn bảo hành') {
            $commonData['replacement'] = $request->customer_refusal_reason;
            $commonData['quantity'] = 0;
            $commonData['unit_price'] = 0;
            $commonData['total'] = 0;
            $commonData['replacement_price'] = 0;

            WarrantyRequestDetail::create($commonData);

            return response()->json(['success' => true, 'created' => true]);
        }

        // Xử lý cho trường hợp "Sửa chữa tại chỗ (lỗi nhẹ)"
        if ($request->solution === 'Sửa chữa tại chỗ (lỗi nhẹ)') {
            $commonData['replacement'] = $request->des_error_type;
            $commonData['quantity'] = 0;
            $commonData['unit_price'] = 0;
            $commonData['total'] = 0;
            $commonData['replacement_price'] = 0;
            
            WarrantyRequestDetail::create($commonData);
            return response()->json(['success' => true, 'created' => true]);
        }

        // Xử lý nhiều linh kiện
        if ($isMultipleComponents) {
            $createdCount = 0;
            $replacements = $request->replacement ?? [];
            $quantities = $request->quantity ?? [];
            $unitPrices = $request->unit_price ?? [];

            foreach ($replacements as $index => $replacement) {
                if (empty($replacement) || 
                    !isset($quantities[$index]) || 
                    $quantities[$index] <= 0) {
                    continue;
                }

                $quantity = (int)($quantities[$index] ?? 0);
                $unitPrice = (int)($unitPrices[$index] ?? 0);
                
                // Tìm giá sản phẩm
                $product = Product::getProductByName($replacement);
                if (!$product) {
                    $view = session('brand') === 'hurom' ? 3 : 1;
                    $product = Product::where('product_name', $replacement)
                        ->where('view', $view)
                        ->first();
                }

                $detailData = array_merge($commonData, [
                    'replacement' => $replacement,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                    'replacement_price' => $product->price ?? $unitPrice,
                ]);

                WarrantyRequestDetail::create($detailData);
                $createdCount++;
            }

            if ($createdCount > 0) {
                return response()->json([
                    'success' => true, 
                    'created' => true,
                    'message' => "Đã lưu {$createdCount} linh kiện thành công."
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng nhập ít nhất một linh kiện hợp lệ.'
                ], 422);
            }
        } else {
            // Xử lý một linh kiện (backward compatible)
            $data = $commonData;
            $data['replacement'] = $request->replacement;
            $data['quantity'] = (int)($request->quantity ?? 0);
            $data['unit_price'] = (int)($request->unit_price ?? 0);
            $data['total'] = $data['quantity'] * $data['unit_price'];

            if ($request->replacement) {
                $product = Product::getProductByName($request->replacement);
                if (!$product) {
                    $view = session('brand') === 'hurom' ? 3 : 1;
                    $product = Product::where('product_name', $request->replacement)
                        ->where('view', $view)
                        ->first();
                }
                $data['replacement_price'] = $product->price ?? $data['unit_price'];
            } else {
                $data['replacement_price'] = 0;
            }

            WarrantyRequestDetail::create($data);
            return response()->json(['success' => true, 'created' => true]);
        }
    }
    //Xoá quá trình
    public function DeleteDetail(Request $request)
    {
        if ($request->id) {
            $detail = WarrantyRequestDetail::find($request->id);

            if ($detail) {
                $deletedCount = WarrantyRequestDetail::where('warranty_request_id', $detail->warranty_request_id)
                    ->where('error_type', $detail->error_type)
                    ->where('solution', $detail->solution)
                    ->where('Ngaytao', $detail->Ngaytao)
                    ->delete();

                return response()->json([
                    'success' => true,
                    'message' => $deletedCount > 1 
                        ? "Đã xóa {$deletedCount} bản ghi thành công." 
                        : 'Xóa bản ghi thành công.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy bản ghi.'
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'ID không hợp lệ.'
        ], 400);
    }

    public function saveRepairJob(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:warranty_repair_jobs,id',
            'warranty_request_id' => 'required|exists:warranty_requests,id',
            'description' => 'required|string|max:1000',
            'component' => 'nullable|string|max:500',
            'quantity' => 'required|numeric|min:0.1|max:1000',
            'unit_price' => 'required|integer|min:0|max:2000000000',
        ]);

        $quantity = round((float) $validated['quantity'], 2);
        $unitPrice = (int) $validated['unit_price'];
        $totalPrice = (int) round($quantity * $unitPrice);

        if ($validated['id'] ?? null) {
            $job = WarrantyRepairJob::findOrFail($validated['id']);
            if ((int) $job->warranty_request_id !== (int) $validated['warranty_request_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiếu công sửa chữa không thuộc ca bảo hành này.'
                ], 422);
            }

            $job->update([
                'description' => $validated['description'],
                'component' => $validated['component'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'created_by' => session('user'),
            ]);

            $message = 'Cập nhật công sửa chữa thành công.';
        } else {
            $job = WarrantyRepairJob::create([
                'warranty_request_id' => $validated['warranty_request_id'],
                'description' => $validated['description'],
                'component' => $validated['component'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'created_by' => session('user'),
            ]);

            $message = 'Thêm công sửa chữa thành công.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $job
        ]);
    }

    public function showRepairJob(WarrantyRepairJob $repairJob)
    {
        return response()->json([
            'success' => true,
            'data' => $repairJob
        ]);
    }

    public function deleteRepairJob(WarrantyRepairJob $repairJob)
    {
        $repairJob->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xoá công sửa chữa.'
        ]);
    }
    // Cập nhật serial
    public function UpdateSerial(Request $request)
    {
        $value = $request->value;
        $type = $request->type;
        $data[$type] = $value;
        if ($request->id) {
            $detail = WarrantyRequest::find($request->id);
            if ($detail) {
                if($type == 'return_date' && ($value == null || $value <= $detail->received_date)){
                    return response()->json([
                        'success' => false, 
                        'message' => "Ngày hẹn trả phải lớn hơn hoặc bằng ngày tiếp nhận.", 
                        'old_value' => $detail->return_date,
                    ]);
                }
                if($type == 'shipment_date' && ($value == null || $value >= $detail->received_date)){
                    return response()->json([
                        'success' => false, 
                        'message' => "Ngày xuất kho phải nhỏ hơn hoặc bằng ngày tiếp nhận.",
                        'old_value' => $detail->shipment_date,
                    ]);
                }
                if($type == 'serial_number' && $value == null){
                    return response()->json([
                        'success' => false,
                        'message' => "Số seri tem bảo hành không được để trống.",
                        'old_value' => $detail->serial_number,
                    ]);
                }
                if($type == 'address'){
                    // Validate địa chỉ: chữ, số và các ký tự '().,-', tối đa 100 ký tự
                    if($value != null && strlen($value) > 100){
                        return response()->json([
                            'success' => false,
                            'message' => "Địa chỉ tối đa 100 ký tự.",
                            'old_value' => $detail->address,
                        ]);
                    }
                    // Kiểm tra ký tự hợp lệ (chữ, số, khoảng trắng, và các ký tự đặc biệt: ().,-)
                    if($value != null && !preg_match('/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ().,\-]+$/', $value)){
                        return response()->json([
                            'success' => false,
                            'message' => "Chỉ cho phép chữ, số và các ký tự '().,-'",
                            'old_value' => $detail->address,
                        ]);
                    }
                }
                $detail->update($data);
                return response()->json(['success' => true, 'message' => "Cập nhật thành công"]);
            }
        }
        return response()->json(['success' => false, 'message' => "Không tìm thấy bản ghi"]);
    }

        //thêm ảnh
        public function UploadPhoto(Request $request)
    {
        $photos = [];
        $warranty = WarrantyRequest::find($request->id);
        
        if (!$warranty) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy phiếu bảo hành']);
        }
        
        // Ảnh lỗi hay ảnh tiếp nhận?
        $isError = $request->boolean('is_error');

        // Bắt buộc phải có ít nhất 1 file ảnh được chọn
        if (!$request->hasFile('photos')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn ít nhất một ảnh để tải lên.'
            ]);
        }

        // Xử lý ảnh
        foreach ($request->file('photos') as $photo) {
            // Giới hạn 3MB mỗi ảnh (an toàn phía server)
            if ($photo->getSize() > 3 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mỗi ảnh tải lên phải nhỏ hơn hoặc bằng 3MB.'
                ]);
            }

            $path = $photo->store('photos', 'public');
            $photos[] = $path;
        }
        
        if (!empty($photos)) {
            if ($isError) {
                // Ảnh lỗi: mỗi lần upload tạo MỘT bản ghi mới trong warranty_upload_error
                $uploadError = new WarrantyUploadError();
                $uploadError->warranty_request_id = $warranty->id;
                $uploadError->image_upload_error = implode(',', $photos);

                $noteContent = trim((string) $request->input('note_error'));
                if ($noteContent !== '') {
                    // Chỉ lưu nội dung ghi chú, KHÔNG kèm thời gian
                    $uploadError->note_error = $noteContent;
                }

                $uploadError->save();
            } else {
                // Ảnh tiếp nhận: lưu vào cột image_upload của warranty_requests
                if (!empty($warranty->image_upload)) {
                    $existingPhotos = explode(',', $warranty->image_upload);
                    $photos = array_merge($existingPhotos, $photos);
                }
                $warranty->image_upload = implode(',', $photos);
                $warranty->save();
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Upload thành công',
        ]);
    }
        //thêm video
        public function UploadVideo(Request $request)
    {
        $videoPath = null;
        $warranty = WarrantyRequest::find($request->id);
        
        if (!$warranty) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy phiếu bảo hành']);
        }
        
        // Video lỗi hay video tiếp nhận?
        $isError = $request->boolean('is_error');

        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('videos', 'public');
            if ($videoPath) {
                if ($isError) {
                    // Video lỗi: 
                    // - Nếu đã có bản ghi với image_upload_error và video_upload_error = NULL, thì cập nhật bản ghi đó
                    // - Nếu chưa có bản ghi nào hoặc bản ghi hiện tại đã có cả image và video, thì tạo bản ghi mới
                    $existingRecord = WarrantyUploadError::where('warranty_request_id', $warranty->id)
                        ->whereNotNull('image_upload_error')
                        ->whereRaw('TRIM(image_upload_error) <> ""')
                        ->whereNull('video_upload_error')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($existingRecord) {
                        // Cập nhật bản ghi hiện có
                        $existingRecord->video_upload_error = $videoPath;
                        $existingRecord->save();
                    } else {
                        // Tạo bản ghi mới
                        $uploadError = new WarrantyUploadError();
                        $uploadError->warranty_request_id = $warranty->id;
                        $uploadError->video_upload_error = $videoPath;
                        $uploadError->save();
                    }
                } else {
                    // Video tiếp nhận: lưu vào cột video_upload của warranty_requests
                    $warranty->video_upload = $videoPath;
                    $warranty->save();
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Upload thành công',
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Không có file'
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Upload thất bại']);
    }

    public function CheckWarranty()
    {
        return view('warranty.checkwarranty');
    }

    public function GeneratePdf($id)
    {
        // Eager load details và repairJobs để tránh N+1 queries
        $data = WarrantyRequest::with(['details', 'repairJobs'])->findOrFail($id);
        $items = $data->details;
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->unit_price;
        }
        
        // Lấy công sửa chữa và tính tổng
        $repairJobs = $data->repairJobs->sortBy('created_at')->values();
        $repairJobsTotal = $repairJobs->sum('total_price');
        
        // Tổng tiền bao gồm cả linh kiện và công sửa chữa
        $grandTotal = $total + $repairJobsTotal;
        
        $ctv = null;
        if ($data->type == 'agent_component') {
            $ctv = [
                'tenctv' => $data->collaborator_name,
                'sdt' => $data->collaborator_phone,
                'diachi' => $data->collaborator_address,
            ];
        }
        $name = 'CÔNG TY TNHH KUCHEN';
        $hotline = '1900 8071';
        $website = 'kuchen.vn';
        if (session('brand') === 'hurom') {
            $name = 'HUROM';
            $hotline = '1900 9056';
            $website = 'hurom-vietnam.vn';
        }

        $city = 'vinh';
        $address = 'Kuchen Building, Đ.Vinh-Cửa Lò, xóm 13, P.Vinh Phú, tỉnh Nghệ An';

        $branch = mb_strtolower($data->branch, 'UTF-8');
        if (Str::contains($branch, 'hcm')) {
            $city = 'hcm';
            $address = 'Lô A1_11 đường D5, KDC Phú Nhuận, phường Phước Long, TP. Hồ Chí Minh';
        } elseif (Str::contains($branch, 'hà nội')) {
            $city = 'hà nội';
            $address = 'Số 136, đường Cổ Linh, P. Long Biên, TP. Hà Nội';
        }
        // Lấy tháng bảo hành của sản phẩm
        $month = Product::where('product_name', $data->product)->value('month') ?? 0;
        
        $warrantyDate = Carbon::parse($data->shipment_date)->addMonths($month);
        $strWar = $warrantyDate < Carbon::now() ? 'Hết hạn bảo hành' : 'Còn hạn bảo hành';
        $paymentQr = $this->buildPaymentQr($data, $grandTotal);

        // Tạo PDF
        return PDF::loadView('warranty.print', compact('data', 'items', 'total', 'repairJobs', 'repairJobsTotal', 'grandTotal', 'name', 'city', 'website', 'address', 'hotline', 'strWar', 'ctv', 'paymentQr'))
            ->setPaper('A4')
            ->stream("phieu-bao-hanh-{$id}.pdf");
    }

    public function DowloadPdf($id)
    {
        // Eager load details và repairJobs để tránh N+1 queries
        $data = WarrantyRequest::with(['details', 'repairJobs'])->findOrFail($id);
        $items = $data->details;
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->unit_price;
        }
        
        // Lấy công sửa chữa và tính tổng
        $repairJobs = $data->repairJobs->sortBy('created_at')->values();
        $repairJobsTotal = $repairJobs->sum('total_price');
        
        // Tổng tiền bao gồm cả linh kiện và công sửa chữa
        $grandTotal = $total + $repairJobsTotal;
        
        $ctv = null;
        if ($data->type == 'agent_component') {
            $ctv = WarrantyCollaborator::getById($data->collaborator_id);
        }
        $name = 'CÔNG TY TNHH KUCHEN';
        $hotline = '1900 8071';
        $website = 'kuchen.vn';
        if (session('brand') === 'hurom') {
            $name = 'HUROM';
            $hotline = '1900 9056';
            $website = 'hurom-vietnam.vn';
        }

        $city = 'vinh';
        $address = 'Kuchen Building, Đ.Vinh-Cửa Lò, xóm 13, P.Nghi Phú, TP.Vinh, tỉnh Nghệ An';

        $branch = mb_strtolower($data->branch, 'UTF-8');
        if (Str::contains($branch, 'hcm')) {
            $city = 'hcm';
            $address = 'Lô A1_11 đường D5, KDC Phú Nhuận, phường Phước Long B, TP Thủ Đức';
        } elseif (Str::contains($branch, 'hà nội')) {
            $city = 'hà nội';
            $address = 'Số 136, đường Cổ Linh, Q. Long Biên, Hà Nội';
        }
        // Lấy tháng bảo hành của sản phẩm
        $month = Product::where('product_name', $data->product)->value('month') ?? 0;
        
        $warrantyDate = Carbon::parse($data->shipment_date)->addMonths($month);
        $strWar = $warrantyDate < Carbon::now() ? 'Hết hạn bảo hành' : 'Còn hạn bảo hành';
        $paymentQr = $this->buildPaymentQr($data, $grandTotal);

        // Tạo PDF
        $pdf = PDF::loadView('warranty.print', compact(
            'data', 'items', 'total', 'repairJobs', 'repairJobsTotal', 'grandTotal', 'name', 'city', 'website', 'address', 'hotline', 'strWar', 'ctv', 'paymentQr'
        ))->setPaper('A4');
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=phieu-bao-hanh-{$id}.pdf",
        ]);
    }
    
    public function Request($id)
    {
        $warrantyRequest = WarrantyRequest::findOrFail($id);
        $lstWarrantyRequestDetails = WarrantyRequestDetail::where('warranty_request_id', $id)->get();

        // Cập nhật request
        $warrantyRequest->print_request = 1;
        $warrantyRequest->save();

        // Lưu vào history request
        $data = Arr::except($warrantyRequest->toArray(), ['id']);
        $data['warranty_id'] = $warrantyRequest->id;
        $data['request_by'] = session('user');
        $data['Ngaytao'] = Carbon::now('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s');
        $item = WarrantyRequestsHistory::create($data);

        // Lưu chi tiết vào history
        foreach ($lstWarrantyRequestDetails as $detail) {
            $detailData = Arr::except($detail->toArray(), ['id']);
            $detailData['warranty_request_history_id'] = $item->id;
            WarrantyRequestDetailsHistory::create($detailData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Yêu cầu in phiếu thành công!'
        ]);
    }

    /**
     * Xây dựng thông tin QR thanh toán VietQR cho phiếu bảo hành.
     */
    private function buildPaymentQr(WarrantyRequest $data, float $total): ?array
    {
        $config = config('services.vietqr', []);
        $bankId = $config['bank_id'] ?? null;
        $accountNo = $config['account_no'] ?? null;
        $accountName = $config['account_name'] ?? null;

        if (!$bankId || !$accountNo || !$accountName) {
            return null;
        }

        $amount = max(0, (int) round($total));
        $description = "TTBH so phieu {$data->id}";
        $qrPayLink = sprintf(
            'https://img.vietqr.io/image/%s-%s-qr_only.jpg?amount=%s&addInfo=%s&accountName=%s',
            rawurlencode($bankId),
            rawurlencode($accountNo),
            rawurlencode($amount),
            rawurlencode($description),
            rawurlencode($accountName)
        );

        try {
            $response = Http::timeout(10)->get($qrPayLink);
            if ($response->successful()) {
                return [
                    'title' => $description,
                    'image' => 'data:image/png;base64,' . base64_encode($response->body()),
                    'amount' => $amount,
                    'account_no' => $accountNo,
                    'account_name' => $accountName,
                    'description' => $description,
                ];
            }
        } catch (\Throwable $exception) {
            Log::warning('Generate payment QR failed', [
                'error' => $exception->getMessage(),
                'warranty_id' => $data->id,
            ]);
        }

        return null;
    }

    public function GetPaymentQr($id)
    {
        $data = WarrantyRequest::findOrFail($id);
        $items = $data->details;
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->unit_price;
        }

        $qr = $this->buildPaymentQr($data, $total);

        if (!$qr) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo QR thanh toán. Vui lòng kiểm tra cấu hình tài khoản.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $qr,
        ]);
    }

    // form nhập phiếu bảo hành
    public function FormWarrantyCard(Request $request)
    {
        // $parts = explode(' ', session('zone'));
        // $chinhanh = strtoupper(session('brand')) . ' ' . end($parts);
        $parts = explode(' ', session('zone'));
        $zoneWithoutFirst = implode(' ', array_slice($parts, 1));
        $chinhanh = strtoupper(session('brand')) . ' ' . $zoneWithoutFirst;
        $warranty = json_decode($request->input('warranty'));
        $lstproduct = json_decode($request->input('lstproduct'));
        $provinces = Province::orderBy('name')->get();
        $products = [];
        if (session('brand') == 'kuchen') {
            $products = Product::where('view', '1')->select('product_name', 'check_seri')->get()->toArray();
        } else {
            $products = Product::where('view', '3')->select('product_name', 'check_seri')->get()->toArray();
        }
        return view('warranty.formwarranty', compact('warranty', 'lstproduct', 'products', 'chinhanh', 'provinces'));
    }
    
    public function FindWarranty(Request $request)
    {
        $view = session('brand') === 'hurom' ? 3 : 1;
        try {
            $serialNumber = strtolower($request->input('serial_number'));
            $warrantyData = ProductWarranty::with(['order_product.order'])
                ->whereRaw('LOWER(warranty_code) = ?', [$serialNumber])
                ->first();
            $serialNumber = $warrantyData?->warranty_code ?? $serialNumber;
            if (!$warrantyData) {
                $warrantyData = WarrantyActive::where('serial', $serialNumber)->first();
                if($warrantyData){
                    $product = Product::where('product_name', $warrantyData->product)
                            ->where('view', $view)
                            ->select('product_name', 'month')->first();
                    if (!$product) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Không tìm thấy thông tin bảo hành cho mã đã nhập.'
                        ]);  
                    }
                    $product->warranty_code = strtoupper($serialNumber);
                    $lstproduct[] = $product;
                    // Lịch sử bảo hành từ database mặc định
                    $warranty = WarrantyRequest::whereRaw('LOWER(serial_number) = ?', [$serialNumber])->first();
                    $history = $warranty ? $warranty->details()->with('warrantyRequest:id,received_date')->get() : [];
    
                    // Render view
                    $view = view('components.warranty_info', [
                        'warranty' => $warrantyData,
                        'lstproduct' => $lstproduct,
                        'product_warranty' => $warranty?->product,
                        'received_warranty' => $warranty?->staff_received,
                        'received_date' => $warranty?->received_date,
                        'history' => $history
                    ])->render();
    
                    return response()->json([
                        'success' => true,
                        'view' => $view,
                        'message' => 'Thông tin bảo hành'
                    ]);
                }
                
            }

            if (!$warrantyData) {
                // Chỉ áp dụng logic suffix cho mã cũ bị lỗi (có prefix 2025050500)
                $baseCodes = Enum::getCodes();
                $isOldErrorCode = false;
                
                // Kiểm tra xem mã nhập vào có phải là mã cũ bị lỗi không
                foreach ($baseCodes as $baseCode) {
                    $normalizedBaseCode = strtolower($baseCode);
                    if (
                        strpos($serialNumber, $normalizedBaseCode) === 0
                        && strlen($serialNumber) === strlen($normalizedBaseCode) + 3
                    ) {
                        $isOldErrorCode = true;
                        break;
                    }
                }
                
                // Chỉ áp dụng logic suffix nếu là mã cũ bị lỗi
                if ($isOldErrorCode) {
                    $suffix = substr($serialNumber, -3);
                    $finalCodes = array_map(function ($code) use ($suffix) {
                        return $code . $suffix;
                    }, $baseCodes);
                    $warrantyData = ProductWarranty::with(['order_product.order'])
                        ->whereIn('warranty_code', $finalCodes)
                        ->first();
                    $serialNumber = $warrantyData?->warranty_code ?? $serialNumber;
                }
            }

            if (!$warrantyData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin bảo hành cho mã đã nhập.'
                ]);
            }

            // Danh sách sản phẩm trong đơn hàng
            $orderId = $warrantyData->order_product->order->id ?? null;
            $lstproduct = [];

            if ($orderId) {
                $lstproduct = OrderProduct::where('order_products.order_id', $orderId)
                    ->where('p.view', $view)
                    ->leftJoin('product_warranties as pw', 'order_products.id', '=', 'pw.order_product_id')
                    ->leftJoin('products as p', 'order_products.product_name', '=', 'p.product_name')
                    ->select('order_products.product_name', 'p.month', 'pw.warranty_code')
                    ->get();
            }

            if($lstproduct->isEmpty()){
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin bảo hành cho mã đã nhập.'
                ]);
            }

            // Lịch sử bảo hành từ database mặc định
            $warranty = WarrantyRequest::whereRaw('LOWER(serial_number) = ?', [$serialNumber])->first();
            $history = $warranty ? $warranty->details()->with('warrantyRequest:id,received_date')->get() : [];

            // Render view
            $view = view('components.warranty_info', [
                'warranty' => $warrantyData,
                'lstproduct' => $lstproduct,
                'product_warranty' => $warranty?->product,
                'received_warranty' => $warranty?->staff_received,
                'received_date' => $warranty?->received_date,
                'history' => $history
            ])->render();

            return response()->json([
                'success' => true,
                'view' => $view,
                'message' => 'Thông tin bảo hành'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi FindWarranty: ' . $e->getMessage());
            return response()->json([
                'Lỗi FindWarranty' => $e->getMessage(),
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý.'
            ], 500);
        }
    }

    public function FindWarrantyByOrderCode(Request $request)
    {
        $view = session('brand') === 'hurom' ? 3 : 1;
        try {
            $orderCode = $request->input('order_code');
            
            if (empty($orderCode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng nhập mã đơn hàng.'
                ]);
            }

            // Tìm đơn hàng theo order_code1 hoặc order_code2
            $order = Order::where('order_code1', $orderCode)
                ->orWhere('order_code2', $orderCode)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng với mã: ' . $orderCode
                ]);
            }

            // Lấy danh sách sản phẩm trong đơn hàng có bảo hành
            $lstproduct = OrderProduct::where('order_products.order_id', $order->id)
                ->where('p.view', $view)
                ->leftJoin('product_warranties as pw', 'order_products.id', '=', 'pw.order_product_id')
                ->leftJoin('products as p', 'order_products.product_name', '=', 'p.product_name')
                ->select('order_products.product_name', 'p.month', 'pw.warranty_code')
                ->get();

            if ($lstproduct->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm bảo hành trong đơn hàng này.'
                ]);
            }

            // Tạo object warranty giả lập từ thông tin đơn hàng để hiển thị
            $warrantyData = (object) [
                'order_product' => (object) [
                    'order' => $order
                ],
                'full_name' => $order->customer_name,
                'phone_number' => $order->customer_phone,
                'address' => $order->customer_address,
                'shipment_date' => $order->created_at ? Carbon::parse($order->created_at) : null,
                'warranty_end' => null
            ];

            // Lấy lịch sử bảo hành của tất cả sản phẩm trong đơn hàng
            $warrantyCodes = $lstproduct->pluck('warranty_code')->filter()->toArray();
            $warranties = WarrantyRequest::whereIn('serial_number', $warrantyCodes)
                ->orderBy('received_date', 'desc')
                ->get();

            // Tạo history từ tất cả các warranty requests
            $history = collect();
            foreach ($warranties as $warranty) {
                $details = $warranty->details()->with('warrantyRequest:id,received_date')->get();
                if ($details->isEmpty()) {
                    // Nếu không có details, vẫn thêm warranty vào history
                    $history->push((object) [
                        'error_type' => null,
                        'solution' => null,
                        'replacement' => null,
                        'warrantyRequest' => (object) [
                            'received_date' => $warranty->received_date
                        ],
                        'product_name' => $warranty->product,
                        'serial_number' => $warranty->serial_number,
                        'staff_received' => $warranty->staff_received
                    ]);
                } else {
                    foreach ($details as $detail) {
                        $history->push((object) [
                            'error_type' => $detail->error_type,
                            'solution' => $detail->solution,
                            'replacement' => $detail->replacement,
                            'warrantyRequest' => (object) [
                                'received_date' => $warranty->received_date
                            ],
                            'product_name' => $warranty->product,
                            'serial_number' => $warranty->serial_number,
                            'staff_received' => $warranty->staff_received
                        ]);
                    }
                }
            }

            // Render view với thông tin đơn hàng
            $viewHtml = view('components.warranty_info', [
                'warranty' => $warrantyData,
                'lstproduct' => $lstproduct,
                'product_warranty' => null,
                'received_warranty' => null,
                'received_date' => null,
                'history' => $history
            ])->render();

            return response()->json([
                'success' => true,
                'view' => $viewHtml,
                'message' => 'Thông tin bảo hành đơn hàng: ' . $orderCode
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi FindWarrantyByOrderCode: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý: ' . $e->getMessage()
            ], 500);
        }
    }

    public function FindWarrantyByPhone(Request $request)
    {
        $view = session('brand') === 'hurom' ? 3 : 1;
        try {
            $rawPhone = trim((string) $request->input('phone_number'));
            $normalizedPhone = preg_replace('/\D+/', '', $rawPhone);

            if (!$normalizedPhone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng nhập số điện thoại hợp lệ.'
                ]);
            }

            if (strlen($normalizedPhone) < 8) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số điện thoại phải có ít nhất 8 chữ số.'
                ]);
            }

            $normalizedPattern = '%' . $normalizedPhone . '%';
            $phoneNormalizer = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, ' ', ''), '.', ''), '-', ''), '(', ''), ')', ''), '+', '')";
            $warrantyPhoneColumn = sprintf($phoneNormalizer, 'phone_number');

            $warrantyRequests = WarrantyRequest::with(['details'])
                ->where('view', $view)
                ->whereRaw($warrantyPhoneColumn . ' LIKE ?', [$normalizedPattern])
                ->orderByDesc('received_date')
                ->orderByDesc('id')
                ->get();

            $warrantyIds = $warrantyRequests->pluck('id')->filter()->toArray();

            $repairHistory = collect();
            if (!empty($warrantyIds)) {
                $repairHistory = WarrantyRequestDetail::with([
                    'warrantyRequest' => function ($relation) {
                        $relation->select('id', 'product', 'serial_number', 'staff_received', 'received_date');
                    }
                ])
                    ->whereIn('warranty_request_id', $warrantyIds)
                    ->orderByDesc('Ngaytao')
                    ->get();
            }

            // 1. Tìm tất cả đơn hàng theo SĐT trong bảng orders
            $orderPhoneColumnRaw = sprintf($phoneNormalizer, 'customer_phone');
            $orders = Order::query()
                ->whereRaw($orderPhoneColumnRaw . ' LIKE ?', [$normalizedPattern])
                ->select('id', 'order_code1', 'order_code2', 'customer_name', 'customer_phone', 'created_at')
                ->get();

            $orderIds = $orders->pluck('id')->filter()->toArray();

            // 2. Lấy danh sách sản phẩm đã mua từ order_products cho các đơn hàng ở trên
            $purchasedProducts = collect();
            if (!empty($orderIds)) {
                $purchasedProducts = OrderProduct::query()
                    ->leftJoin('orders as o', 'order_products.order_id', '=', 'o.id')
                    ->leftJoin('product_warranties as pw', 'order_products.id', '=', 'pw.order_product_id')
                    ->leftJoin('products as p', 'order_products.product_name', '=', 'p.product_name')
                    ->whereIn('o.id', $orderIds)
                    ->where('p.view', $view)
                    ->select(
                        'o.order_code1',
                        'o.order_code2',
                        'o.customer_name',
                        'o.customer_phone',
                        'o.customer_address',
                        'o.created_at as order_created_at',
                        'order_products.product_name',
                        'p.month',
                        'pw.warranty_code'
                    )
                    ->orderByDesc('o.created_at')
                    ->get();
            }

            if ($warrantyRequests->isEmpty() && $purchasedProducts->isEmpty() && $repairHistory->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy dữ liệu cho số điện thoại đã nhập.'
                ]);
            }

            $viewHtml = view('components.warranty_phone_result', [
                'warrantyRequests' => $warrantyRequests,
                'purchasedProducts' => $purchasedProducts,
                'repairHistory' => $repairHistory,
                'phoneDisplay' => $rawPhone ?: $normalizedPhone
            ])->render();

            return response()->json([
                'success' => true,
                'view' => $viewHtml,
                'message' => 'Thông tin tra cứu theo số điện thoại'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi FindWarrantyByPhone: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý: ' . $e->getMessage()
            ], 500);
        }
    }

    public function findWarantyOld(Request $request)
    {
        $serial = $request->serial;
        if (ctype_digit($serial)) {
            return response()->json(['success' => false, 'type' => 0]);
        }
        $result = TemBaoHanh::where('serial', $serial)->orWhere('ma_pin', $serial)->first();
        $khachHang = null;
        if(!$result){
            return response()->json(['success' => false, 'type' => 1]);
        }
        $khachHang = KhachHang::where('serial', $result->serial)->first();
        return response()->json([
            'success' => true,
            'tem' => $result,
            'khach_hang' => $khachHang
        ]);
    }

    public function FindWarrantyQR(Request $request)
    {
        try {
            $serialNumber = strtolower($request->input('serial_number'));
            $suffix = substr($serialNumber, -3);
            $baseCodes = Enum::getCodes();
            $finalCodes = array_map(function ($code) use ($suffix) {
                return $code . $suffix;
            }, $baseCodes);
            
            $warrantyData = ProductWarranty::with(['order_product.order'])
                ->whereRaw('LOWER(warranty_code) = ?', [$serialNumber])
                ->first();
            if (!$warrantyData){
                $possibleOldCode = false;
                foreach ($baseCodes as $code) {
                    $normalizedBaseCode = strtolower($code);
                    if (
                        strpos($serialNumber, $normalizedBaseCode) === 0
                        && strlen($serialNumber) === strlen($normalizedBaseCode) + 3
                    ) {
                        $possibleOldCode = true;
                        break;
                    }
                }

                if ($possibleOldCode) {
                $warrantyData = ProductWarranty::with(['order_product.order'])
                    ->whereIn('warranty_code', $finalCodes)
                    ->first();
                $serialNumber = $warrantyData?->warranty_code ?? $serialNumber;
                }
            }

            if (!$warrantyData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin bảo hành cho mã ' . $serialNumber
                ]);
            }

            // Danh sách sản phẩm trong đơn hàng
            $orderId = $warrantyData->order_product->order->id ?? null;
            $lstproduct = [];

            if ($orderId) {
                $lstproduct = ProductWarranty::where('warranty_code', $serialNumber)
                    ->leftJoin('order_products as op', 'product_warranties.order_product_id', '=', 'op.id')
                    ->leftJoin('products as p', 'op.product_name', '=', 'p.product_name')
                    ->select('op.product_name', 'p.nhap_tay', 'product_warranties.warranty_code')->get();
            }

            

            return response()->json([
                'success' => true,
                'warranty' => $warrantyData,
                'lstproduct' => $lstproduct,
                'message' => 'Thông tin bảo hành '. $serialNumber
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi FindWarranty: ' . $e->getMessage());
            return response()->json([
                'Lỗi FindWarranty' => $e->getMessage(),
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý.'
            ], 500);
        }
    }

    public function getCollaboratorByPhoneNumber(Request $request){
        $phone = $request->phone;
        $item = WarrantyCollaborator::where('phone', $phone)->first();
        if(!$item){
            return response()->json(['success' => false, 'message' => "không tìm thấy cộng tác viên có số điện thoại " . $phone]);
        }
        return response()->json(['success'=> true, 'message'=> 'ok', 'data'=> $item]);
    }
    public function CreateWarrany(Request $request)
    {
        

        $view = Session('brand') === 'hurom' ? 3 : 1;
        $name = $request->product;
        $product = Product::where('product_name', $name)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm cũ, vui lòng liên hệ chuyên viên để giải quyết.'
            ]);
        }
        $shipmentDate = Carbon::createFromFormat('d/m/Y', $request->shipment_date);
        $warrantyEnd = $shipmentDate->copy()->addMonths($product->month);
        
        // Kiểm tra anomaly và chặn nếu vượt ngưỡng
        $staffName = session('user');
        $branch = $request->branch;
        $anomalyDetector = new WarrantyAnomalyDetector();
        $anomalyCheck = $anomalyDetector->checkAndBlock($staffName, $branch);
        
        if ($anomalyCheck['blocked']) {
            return response()->json([
                'success' => false,
                'message' => $anomalyCheck['message'],
                'block_info' => $anomalyCheck['block_info']
            ], 403);
        }
        
        //Kiểm tra trùng lặp trong 48 giờ (toàn hệ thống)
        $serialNumber = $request->serial_number;
        $serialThanMay = $request->serial_thanmay;
        $productName = $request->product;
        $customerPhone = $request->phone_number;
        
        // Kiểm tra trùng trong 48 giờ
        $duplicateCheck = $this->checkDuplicateWarranty(
            $serialNumber,
            $serialThanMay,
            $productName,
            $customerPhone
        );
        
        if ($duplicateCheck['exists']) {
            $existingWarranty = $duplicateCheck['warranty'];
            $hoursAgo = Carbon::parse($existingWarranty->received_date)->diffInHours(now());
            $daysAgo = Carbon::parse($existingWarranty->received_date)->diffInDays(now());
            
            return response()->json([
                'success' => false,
                'message' => $duplicateCheck['message'],
                'existing_warranty' => [
                    'id' => $existingWarranty->id,
                    'serial_number' => $existingWarranty->serial_number,
                    'serial_thanmay' => $existingWarranty->serial_thanmay,
                    'received_date' => $existingWarranty->received_date,
                    'staff_received' => $existingWarranty->staff_received,
                    'full_name' => $existingWarranty->full_name,
                    'phone_number' => $existingWarranty->phone_number,
                    'status' => $existingWarranty->status,
                    'branch' => $existingWarranty->branch,
                    'hours_ago' => $hoursAgo,
                    'days_ago' => $daysAgo,
                    'product' => $existingWarranty->product
                ]
            ], 409); // HTTP 409 Conflict
        }
        
        $pw = ProductWarranty::with('order_product.order')
            ->where('warranty_code', $request->serial_number)
            ->first();
        $agency_name = $pw->order_product->order->agency_name ?? '';
        $agency_phone = $pw->order_product->order->agency_phone ?? '';

        // Lưu vào DB
        $warranty = WarrantyRequest::create([
            'product' => $request->product,
            'serial_number' => $request->serial_number,
            'serial_thanmay' => $request->serial_thanmay,
            'type' => $request->type,
            'collaborator_id' => $request->collaborator_id,
            'collaborator_name' => $request->collaborator_name,
            'collaborator_phone' => $request->collaborator_phone,
            'collaborator_address' => $request->collaborator_address,
            'full_name' => $request->full_name,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'staff_received' => session('user'),
            'received_date' => Carbon::now(),
            'warranty_end' => $warrantyEnd->format('Y-m-d'),
            'branch' => $request->branch,
            'shipment_date' => Carbon::createFromFormat('d/m/Y', $request->shipment_date)->format('Y-m-d'),
            'return_date' => Carbon::createFromFormat('d/m/Y', $request->return_date)->format('Y-m-d'),
            'initial_fault_condition' => $request->initial_fault_condition,
            'product_fault_condition' => $request->product_fault_condition,
            'product_quantity_description' => $request->product_quantity_description,
            'view'=> $view,
            'province_id' => $request->province_id,
            'district_id' => $request->district_id,
            'ward_id' => $request->ward_id,
            'agency_name' => $agency_name,
            'agency_phone' => $agency_phone,
        ]);

        return response()->json([
            'success' => true,
            'id' => $warranty->id,
            'message' => 'Tạo phiếu bảo hành thành công.'
        ]);
    }
    public function TakePhotoWarranty(Request $request)
    {
        $id = $request->query('sophieu');
        return view('warranty.takephoto', compact('id'));
    }
    //Lưu hỉnh ảnh và video (chỉ lưu vào warranty_requests, không lưu vào warranty_upload_error)
    // Chỉ khi upload lỗi (is_error = true) mới tạo bản ghi trong warranty_upload_error
    public function StoreMedia(Request $request)
    {
        $photos = [];
        $videoPath = null;
        $warranty = WarrantyRequest::find($request->id);

        if (!$warranty) {
            return response()->json([
                'message' => 'Bạn chưa tạo phiếu bảo hành!',
                'success' => false
            ], 404);
        }
        
        // Xử lý ảnh - chỉ lưu vào warranty_requests (không phải lỗi)
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('photos', 'public');
                $photos[] = $path;
            }
            if (!empty($photos)) {
                // Chỉ lưu vào warranty_requests (upload tiếp nhận, không phải lỗi)
                if (!empty($warranty->image_upload)) {
                    $existingPhotos = explode(',', $warranty->image_upload);
                    $photos = array_merge($existingPhotos, $photos);
                }
                $warranty->image_upload = implode(',', $photos);
            }
        }

        // Xử lý video - chỉ lưu vào warranty_requests (không phải lỗi)
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('videos', 'public');
            if ($videoPath) {
                // Chỉ lưu vào warranty_requests (upload tiếp nhận, không phải lỗi)
                $warranty->video_upload = $videoPath;
            }
        }
        
        $warranty->save();

        return response()->json([
            'media_id' => $request->id,
            'photos' => $photos,
            'video' => $videoPath,
            'success' => true
        ]);
    }

    /**
     * Trang xem cảnh báo anomaly (chỉ admin)
     */
    public function AnomalyAlertsPage()
    {
        // Chỉ admin hoặc quản trị viên mới được xem
        $position = strtolower(session('position') ?? '');
        if (!in_array($position, ['admin', 'quản trị viên'])) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return view('warranty.anomaly_alerts');
    }

    /**
     * Lấy danh sách cảnh báo anomaly (chỉ admin) - API
     */
    public function getAnomalyAlerts(Request $request)
    {
        // Chỉ admin hoặc quản trị viên mới được xem
        $position = strtolower(session('position') ?? '');
        if (!in_array($position, ['admin', 'quản trị viên'])) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem cảnh báo này.'
            ], 403);
        }

        $date = $request->input('date');
        $branch = $request->input('branch');
        $resolved = $request->input('resolved');

        $anomalyDetector = new WarrantyAnomalyDetector();
        $alerts = $anomalyDetector->getAlerts($date, $branch, $resolved);

        // Thêm thông tin block vào mỗi alert
        $alertsData = $alerts->map(function ($alert) {
            $block = WarrantyAnomalyBlock::where('staff_name', $alert->staff_name)
                ->where('branch', $alert->branch)
                ->where('date', $alert->date)
                ->where('is_active', true)
                ->where('blocked_until', '>', now())
                ->first();
            
            // Chuyển sang array và thêm field mới
            $alertData = $alert->toArray();
            $alertData['has_active_block'] = $block !== null;
            return $alertData;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $alertsData
        ]);
    }

    /**
     * Đánh dấu cảnh báo đã được xử lý
     */
    public function resolveAnomalyAlert(Request $request, $id)
    {
        // Chỉ admin hoặc quản trị viên mới được xử lý
        $position = strtolower(session('position') ?? '');
        if (!in_array($position, ['admin', 'quản trị viên'])) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xử lý cảnh báo này.'
            ], 403);
        }

        $alert = WarrantyAnomalyAlert::find($id);
        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cảnh báo.'
            ], 404);
        }

        $alert->update([
            'is_resolved' => true,
            'resolved_by' => Auth::id(),
            'resolved_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu cảnh báo đã được xử lý.'
        ]);
    }

    /**
     * Gỡ block cho nhân viên
     */
    public function unblockStaff(Request $request, $alertId)
    {
        // Chỉ admin hoặc quản trị viên mới được gỡ block
        $position = strtolower(session('position') ?? '');
        if (!in_array($position, ['admin', 'quản trị viên'])) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền gỡ block này.'
            ], 403);
        }

        $alert = WarrantyAnomalyAlert::find($alertId);
        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cảnh báo.'
            ], 404);
        }

        // Tìm và gỡ tất cả block của nhân viên này trong ngày
        $blocks = WarrantyAnomalyBlock::where('staff_name', $alert->staff_name)
            ->where('branch', $alert->branch)
            ->where('date', $alert->date)
            ->where('is_active', true)
            ->get();

        // Gỡ tất cả block (nếu có)
        if ($blocks->count() > 0) {
            foreach ($blocks as $block) {
                $block->update([
                    'is_active' => false,
                    'blocked_until' => now() // Đảm bảo block hết hiệu lực ngay
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã gỡ block cho nhân viên ' . $alert->staff_name . '. Nhân viên có thể tiếp tục tạo phiếu bảo hành.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy block đang active cho nhân viên này.'
        ], 404);
    }

    /**
     * Xóa cảnh báo
     */
    public function deleteAnomalyAlert(Request $request, $id)
    {
        // Chỉ admin hoặc quản trị viên mới được xóa cảnh báo
        $position = strtolower(session('position') ?? '');
        if (!in_array($position, ['admin', 'quản trị viên'])) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa cảnh báo này.'
            ], 403);
        }

        $alert = WarrantyAnomalyAlert::find($id);
        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy cảnh báo.'
            ], 404);
        }

        // Xóa cảnh báo
        $alert->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa cảnh báo thành công.'
        ]);
    }

    /**
     * Kiểm tra phiếu bảo hành trùng trong 48 giờ (toàn hệ thống)
     * 
     * @param string|null $serialNumber
     * @param string|null $serialThanMay
     * @param string $productName
     * @param string $customerPhone
     * @return array ['exists' => bool, 'warranty' => WarrantyRequest|null, 'message' => string]
     */
    private function checkDuplicateWarranty(
        $serialNumber, 
        $serialThanMay, 
        $productName, 
        $customerPhone
    ) {
        $hoursAgo = Carbon::now()->subHours(48);
        
        // Normalize dữ liệu: trim và kiểm tra rỗng
        $serialNumber = $serialNumber ? trim($serialNumber) : null;
        $serialThanMay = $serialThanMay ? trim($serialThanMay) : null;
        // Normalize số điện thoại: bỏ tất cả ký tự không phải số
        $customerPhone = $customerPhone ? preg_replace('/[^0-9]/', '', trim($customerPhone)) : null;
        
        // Trường hợp 1: Có serial_number hợp lệ (ưu tiên cao nhất)
        if ($serialNumber && $serialNumber !== '' && $serialNumber !== 'HÀNG KHÔNG CÓ MÃ SERI') {
            $existing = WarrantyRequest::whereRaw('LOWER(serial_number) = ?', [strtolower($serialNumber)])
                ->where('received_date', '>=', $hoursAgo)
                ->orderBy('received_date', 'desc')
                ->first();
                
            if ($existing) {
                return [
                    'exists' => true,
                    'warranty' => $existing,
                    'message' => 'Phiếu bảo hành cho Serial Number này đã tồn tại trong 2 ngày gần nhất.'
                ];
            }
        }
        
        // Trường hợp 2: Không có serial_number hợp lệ nhưng có serial_thanmay
        // Kiểm tra: serial_number rỗng/null/'HÀNG KHÔNG CÓ MÃ SERI' VÀ serial_thanmay có giá trị
        $hasValidSerialNumber = $serialNumber && $serialNumber !== '' && $serialNumber !== 'HÀNG KHÔNG CÓ MÃ SERI';
        $hasValidSerialThanMay = $serialThanMay && $serialThanMay !== '';
        
        if (!$hasValidSerialNumber && $hasValidSerialThanMay) {
            $existing = WarrantyRequest::whereRaw('LOWER(serial_thanmay) = ?', [strtolower($serialThanMay)])
                ->where('received_date', '>=', $hoursAgo)
                ->orderBy('received_date', 'desc')
                ->first();
                
            if ($existing) {
                return [
                    'exists' => true,
                    'warranty' => $existing,
                    'message' => 'Phiếu bảo hành cho Serial thân máy này đã tồn tại trong 2 ngày gần nhất.'
                ];
            }
        }
        
        // Trường hợp 3: Không có cả serial_number và serial_thanmay hợp lệ
        // Kiểm tra theo product + phone_number (lưu ý: phone có thể thay đổi)
        if (!$hasValidSerialNumber && !$hasValidSerialThanMay) {
            // Lấy tất cả records trong 48h với product match, sau đó normalize phone trong PHP
            $candidates = WarrantyRequest::where('product', $productName)
                ->where('received_date', '>=', $hoursAgo)
                ->orderBy('received_date', 'desc')
                ->get();
            
            // So sánh phone đã được normalize (chỉ số)
            $existing = $candidates->first(function ($item) use ($customerPhone) {
                $dbPhone = preg_replace('/[^0-9]/', '', $item->phone_number ?? '');
                return $dbPhone === $customerPhone;
            });
                
            if ($existing) {
                return [
                    'exists' => true,
                    'warranty' => $existing,
                    'message' => 'Phiếu bảo hành cho sản phẩm và số điện thoại này đã tồn tại trong 2 ngày gần nhất.'
                ];
            }
        }
        
        return ['exists' => false, 'warranty' => null, 'message' => ''];
    }

}
