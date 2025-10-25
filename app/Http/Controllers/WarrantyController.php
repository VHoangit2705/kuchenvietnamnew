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
use Barryvdh\DomPDF\Facade\Pdf;

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
            ->when($sdt = request('sdt'), fn($q) => $q->where('phone_number', 'like', "%$sdt%"))
            ->when($khachhang = request('khachhang'), fn($q) => $q->where('full_name', 'like', "%$khachhang%"))
            ->when($kythuatvien = request('kythuatvien'), fn($q) => $q->where('staff_received', 'like', "%$kythuatvien%"))
            ->when($chinhanh = request('chinhanh'), fn($q) => $q->where('branch', 'like', "%$chinhanh%"))
            ->when($product = request('product'), fn($q) => $q->where('product', 'like', "%$product%"));

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

        if (request()->ajax()) {
            return response()->json([
                'tab' => view('components.tabheader', [
                    'counts' => $counts,
                    'activeTab' => $tab
                ])->render(),
                'table' => view('components.tabcontent', compact('data'))->render(),
            ]);
        }

        return view('warranty.homewarranty', compact('data', 'userBranch', 'counts'));
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
            ->when($sdt = request('sdt'), fn($q) => $q->where('phone_number', 'like', "%$sdt%"))
            ->when($khachhang = request('khachhang'), fn($q) => $q->where('full_name', 'like', "%$khachhang%"))
            ->when($kythuatvien = request('kythuatvien'), fn($q) => $q->where('staff_received', 'like', "%$kythuatvien%"))
            ->when($chinhanh = request('chinhanh'), fn($q) => $q->where('branch', 'like', "%$chinhanh%"))
            ->when($product = request('product'), fn($q) => $q->where('product', 'like', "%$product%"));


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

        return view('warranty.homewarranty', compact('data', 'userBranch', 'counts'));
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
                    // $newQty =  $record->quantity - $component['return_quantity'];
                    // if ($newQty <= 0) {
                    //     // Xóa bản ghi nếu số lượng mới bằng hoặc nhỏ hơn 0
                    //     WarrantyRequestDetail::where('id', $component['id'])->delete();
                    // } else {
                    //     // Cập nhật lại số lượng nếu còn
                    //     WarrantyRequestDetail::where('id', $component['id'])->update(['quantity' => $newQty]);
                    // }
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
        $data = WarrantyRequest::where('id', $id)->first();
        $quatrinhsua = WarrantyRequestDetail::where('warranty_request_id', $id)->get();
        $history = WarrantyRequest::where('serial_number', $data->serial_number)->where('phone_number', $data->phone_number)->orderBy('received_date', 'desc')->get();
        $linhkien = Product::where('view', '2')->select('product_name')->get();
        return view('warranty.warrantydetails', compact('data', 'quatrinhsua', 'history', 'linhkien'));
    }
    // cập nhật quá trình sửa chữa
    public function UpdateDetail(Request $request)
    {
        $request->merge([
            'quantity' => (int) $request->quantity
        ]);
        $validator = Validator::make($request->all(), [
            'error_type' => 'required|string|max:255',
            'solution' => 'required|string|max:255',
            'replacement' => 'nullable|string|max:255',
            'quantity' => 'nullable',
            'unit_price' => 'nullable|integer|min:0',
            'des_error_type' => 'nullable',
        ]);

        if ($request->solution === 'Thay thế linh kiện/hardware' && empty($request->replacement)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('replacement', 'Linh kiện thay thế là bắt buộc khi chọn giải pháp này.');
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $validator->validated();
        if($request->solution === 'Sửa chữa tại chỗ (lỗi nhẹ)'){
            $data['replacement'] = $request->des_error_type;
        }
        if($request->replacement){
            $product = Product::getProductByName($request->replacement);
            $data['replacement_price'] = $product->price ?? $request->unit_price;
        }
        // Thêm thông tin bổ sung
        $data['warranty_request_id'] =  $request->warranty_request_id;
        $data['total'] =  $request->quantity * $request->unit_price;
        $data['Ngaytao'] = Carbon::now();
        $data['edit_by'] = session('user');
        if ($request->id) {
            $detail = WarrantyRequestDetail::find($request->id);
            if ($detail) {
                $detail->update($data);
                return response()->json(['success' => true, 'updated' => true]);
            }
        }
        WarrantyRequestDetail::create($data);

        return response()->json(['success' => true, 'created' => true]);
    }
    //Xoá quá trình
    public function DeleteDetail(Request $request)
    {
        if ($request->id) {
            $detail = WarrantyRequestDetail::find($request->id);

            if ($detail) {
                $detail->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Xóa bản ghi thành công.'
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
        // Xử lý ảnh
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('photos', 'public');
                $photos[] = $path;
            }
            if (!empty($photos)) {
                if (!empty($warranty->image_upload)) {
                    $existingPhotos = explode(',', $warranty->image_upload);
                    $photos = array_merge($existingPhotos, $photos);
                }
    
                $warranty->image_upload = implode(',', $photos);
            }
            $warranty->save();
            return response()->json([
                'success' => true,
                'message' => 'Upload thành công',
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Upload thất bại']);
    }
    //thêm video
    public function UploadVideo(Request $request)
    {
        $videoPath = null;
        $warranty = WarrantyRequest::find($request->id);
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('videos', 'public');
            if ($videoPath) {
                $warranty->video_upload = $videoPath;
                $warranty->save();
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
        $data = WarrantyRequest::findOrFail($id);
        $items = $data->details;
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->unit_price;
        }
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
        //
        $month =  Product::where('product_name', $data->product)->value('month');
        if (!$month) {
            $month = 0;
        }
        $warrantyDate = Carbon::parse($data->shipment_date)->addMonths($month);
        $strWar = $warrantyDate < Carbon::now() ? 'Hết hạn bảo hành' : 'Còn hạn bảo hành';
        // Tạo PDF
        return PDF::loadView('warranty.print', compact('data', 'items', 'total', 'name', 'city', 'website', 'address', 'hotline', 'strWar', 'ctv'))
            ->setPaper('A4')
            ->stream("phieu-bao-hanh-{$id}.pdf");
    }

    public function DowloadPdf($id)
    {
        $data = WarrantyRequest::findOrFail($id);
        $items = $data->details;
        $total = 0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->unit_price;
        }
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
        //
        $month =  Product::where('product_name', $data->product)->value('month');
        if (!$month) {
            $month = 0;
        }
        $warrantyDate = Carbon::parse($data->shipment_date)->addMonths($month);
        $strWar = $warrantyDate < Carbon::now() ? 'Hết hạn bảo hành' : 'Còn hạn bảo hành';
        // Tạo PDF
        $pdf = PDF::loadView('warranty.print', compact(
            'data', 'items', 'total', 'name', 'city', 'website', 'address', 'hotline', 'strWar', 'ctv'
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
    
    // public function FindWarranty(Request $request)
    // {
    //     try {
    //         $serialNumber = strtolower($request->input('serial_number'));

    //         // Lấy thông tin bảo hành + orderProduct + order bằng Eloquent
    //         $warrantyData = ProductWarranty::with(['order_product.order'])
    //             ->whereRaw('LOWER(warranty_code) = ?', [$serialNumber])
    //             ->first();

    //         if (!$warrantyData) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Không tìm thấy thông tin bảo hành cho mã đã nhập.'
    //             ]);
    //         }

    //         // Danh sách sản phẩm trong đơn hàng
    //         $orderId = $warrantyData->order_product->order->id ?? null;
    //         $lstproduct = [];

    //         if ($orderId) {
    //             $lstproduct = OrderProduct::where('order_products.order_id', $orderId)
    //                 ->leftJoin('product_warranties as pw', 'order_products.id', '=', 'pw.order_product_id')
    //                 ->leftJoin('products as p', 'order_products.product_name', '=', 'p.product_name')
    //                 ->select('order_products.product_name', 'p.month', 'pw.warranty_code')
    //                 ->get();
    //         }

    //         // Lịch sử bảo hành từ database mặc định
    //         $warranty = WarrantyRequest::whereRaw('LOWER(serial_number) = ?', [$serialNumber])->first();
    //         $history = $warranty ? $warranty->details()->with('warrantyRequest:id,received_date')->get() : [];

    //         // Render view
    //         $view = view('components.warranty_info', [
    //             'warranty' => $warrantyData,
    //             'lstproduct' => $lstproduct,
    //             'product_warranty' => $warranty?->product,
    //             'received_warranty' => $warranty?->staff_received,
    //             'received_date' => $warranty?->received_date,
    //             'history' => $history
    //         ])->render();

    //         return response()->json([
    //             'success' => true,
    //             'view' => $view,
    //             'message' => 'Thông tin bảo hành'
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Lỗi FindWarranty: ' . $e->getMessage());
    //         return response()->json([
    //             'Lỗi FindWarranty' => $e->getMessage(),
    //             'message' => 'Đã xảy ra lỗi trong quá trình xử lý.'
    //         ], 500);
    //     }
    // }
    
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
                $suffix = substr($serialNumber, -3);
                $baseCodes = Enum::getCodes();
                $finalCodes = array_map(function ($code) use ($suffix) {
                    return $code . $suffix;
                }, $baseCodes);
                $warrantyData = ProductWarranty::with(['order_product.order'])
                    ->whereIn('warranty_code', $finalCodes)
                    ->first();
                $serialNumber = $warrantyData?->warranty_code ?? $serialNumber;
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
                $warrantyData = ProductWarranty::with(['order_product.order'])
                    ->whereIn('warranty_code', $finalCodes)
                    ->first();
                $serialNumber = $warrantyData?->warranty_code ?? $serialNumber;
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

            if($lstproduct->isNotEmpty() && $lstproduct->first()->nhap_tay == 1){
                return response()->json(['success' => false, 'message' => 'Sản phẩm này không thể quét.']);
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
        
        //Kiểm tra trùng lặp trước khi tạo phiếu
        $today = Carbon::today();
        $zone = session('zone'); // Dữ liệu lấy từ session
        $serialNumber = $request->serial_number;
        $serialThanMay = $request->serial_thanmay;
        $productName = $request->product;
        $customerPhone = $request->phone_number;
        
        if ($serialNumber === 'HÀNG KHÔNG CÓ MÃ SERI' && empty($serialThanMay)) {
            // 1. Không có serial_number và không có serial_thanmay
            // Nếu cùng khách hàng (số điện thoại trùng) trong cùng chi nhánh và cùng ngày -> không được tạo phiếu
            $existingWarranty = WarrantyRequest::where('phone_number', $customerPhone)
                ->where('branch', $zone)
                ->whereDate('received_date', $today)
                ->first();
        } elseif ($serialNumber === 'HÀNG KHÔNG CÓ MÃ SERI' && !empty($serialThanMay)) {
            // 2. Không có serial_number nhưng có serial_thanmay
            $existingWarranty = WarrantyRequest::where('serial_thanmay', $serialThanMay)
                ->where('branch', $zone)
                ->whereDate('received_date', $today)
                ->first();
        } elseif ($serialNumber !== 'HÀNG KHÔNG CÓ MÃ SERI' && empty($serialThanMay)) {
            // 3. Có serial_number nhưng không có serial_thanmay
            // Kiểm tra trùng serial trong toàn hệ thống (không giới hạn theo nhân viên)
            $existingWarranty = WarrantyRequest::where('serial_number', $serialNumber)
                ->whereDate('received_date', $today)
                ->first();
        } else {
            // 4. Có cả serial_number và serial_thanmay
            // Kiểm tra trùng serial trong toàn hệ thống (không giới hạn theo nhân viên)
            $existingWarranty = WarrantyRequest::where('serial_number', $serialNumber)
                ->where('serial_thanmay', $serialThanMay)
                ->whereDate('received_date', $today)
                ->first();
        }
        
        if ($existingWarranty) {
            $message = 'Phiếu bảo hành đã được tạo hôm nay tại chi nhánh. Vui lòng kiểm tra lại.';
            if ($serialNumber === 'HÀNG KHÔNG CÓ MÃ SERI' && empty($serialThanMay)) {
                $message = 'Khách hàng này đã có phiếu cho sản phẩm này hôm nay. Vui lòng kiểm tra lại.';
            } elseif ($serialNumber !== 'HÀNG KHÔNG CÓ MÃ SERI') {
                $message = 'Phiếu bảo hành đã được tạo hôm nay tại chi nhánh. Vui lòng kiểm tra lại.';
            }
            return response()->json([
                'success' => false,
                'message' => $message,
            ]);
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
            'received_date' => Carbon::today(),
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
    //Lưu hỉnh ảnh và video
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
        // Xử lý ảnh
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('photos', 'public');
                $photos[] = $path;
            }
            if (!empty($photos)) {
                $warranty->image_upload = implode(',', $photos);
            }
        }

        // Xử lý video
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('videos', 'public');
            if ($videoPath) {
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

}