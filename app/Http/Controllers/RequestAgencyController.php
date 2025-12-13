<?php

namespace App\Http\Controllers;

use App\Models\KyThuat\RequestAgency;
use App\Models\Kho\InstallationOrder;
use App\Models\Kho\Agency;
use App\Enum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\Paginator;

Paginator::useBootstrap();

class RequestAgencyController extends Controller
{
    static $pageSize = 50;

    /**
     * Đồng bộ trạng thái từ InstallationOrder sang RequestAgency
     * Chỉ đồng bộ các trạng thái đã được điều phối (Đã điều phối, Hoàn thành, Đã thanh toán)
     * KHÔNG đồng bộ trạng thái "Đã xác nhận đại lý" để người dùng có thể thao tác xác thực đại lý
     */
    private function syncStatusFromInstallationOrder()
    {
        // Chỉ đồng bộ các trạng thái đã được điều phối
        // KHÔNG đồng bộ "Đã xác nhận đại lý" - trạng thái này phải được giữ nguyên
        $requestAgencies = RequestAgency::whereNotNull('order_code')
            ->whereIn('status', [
                RequestAgency::STATUS_DA_XAC_NHAN_AGENCY,
                RequestAgency::STATUS_DA_DIEU_PHOI,
                RequestAgency::STATUS_HOAN_THANH,
                RequestAgency::STATUS_DA_THANH_TOAN
            ])
            ->get();

        foreach ($requestAgencies as $requestAgency) {
            // Tìm InstallationOrder theo order_code
            $installationOrder = InstallationOrder::where('order_code', $requestAgency->order_code)
                ->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID) // Chỉ đồng bộ với đại lý lắp đặt
                ->whereNotNull('status_install')
                ->where('status_install', '>=', 1)
                ->first();

            if ($installationOrder) {
                // Đồng bộ trạng thái với status_install
                $newStatus = match($installationOrder->status_install) {
                    1 => RequestAgency::STATUS_DA_DIEU_PHOI,
                    2 => RequestAgency::STATUS_HOAN_THANH,
                    3 => RequestAgency::STATUS_DA_THANH_TOAN,
                    default => $requestAgency->status
                };

                // Chỉ cập nhật nếu trạng thái khác nhau
                if ($newStatus != $requestAgency->status) {
                    $requestAgency->status = $newStatus;
                    if (!$requestAgency->assigned_to) {
                        $requestAgency->assigned_to = session('user', 'system');
                    }
                    $requestAgency->save();
                }
            }
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Đồng bộ trạng thái từ InstallationOrder trước khi hiển thị
        $this->syncStatusFromInstallationOrder();

        $query = RequestAgency::query();

        // Filter theo trạng thái
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter theo mã đơn hàng
        if ($request->has('order_code') && $request->order_code) {
            $query->where('order_code', 'like', '%' . $request->order_code . '%');
        }

        // Filter theo tên khách hàng
        if ($request->has('customer_name') && $request->customer_name) {
            $query->where('customer_name', 'like', '%' . $request->customer_name . '%');
        }

        // Filter theo số điện thoại
        if ($request->has('customer_phone') && $request->customer_phone) {
            $query->where('customer_phone', 'like', '%' . $request->customer_phone . '%');
        }

        // Filter theo tên đại lý (tìm trong bảng agency qua quan hệ)
        if ($request->has('agency_name') && $request->agency_name) {
            $query->whereHas('agency', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->agency_name . '%');
            });
        }

        // Filter theo số điện thoại đại lý (tìm trong bảng agency qua quan hệ)
        if ($request->has('agency_phone') && $request->agency_phone) {
            $query->whereHas('agency', function($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->agency_phone . '%');
            });
        }

        // Filter theo CCCD đại lý (tìm trong bảng agency qua quan hệ)
        if ($request->has('agency_cccd') && $request->agency_cccd) {
            $query->whereHas('agency', function($q) use ($request) {
                $q->where('cccd', 'like', '%' . $request->agency_cccd . '%');
            });
        }

        // Filter theo ngày tạo
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sắp xếp: mới nhất trước
        $query->orderByDesc('created_at');

        // Eager load quan hệ agency
        $query->with('agency');

        // Phân trang
        $requests = $query->paginate(self::$pageSize)->withQueryString();

        // Kiểm tra và đánh dấu các request có đại lý khác đã dùng mã đơn hàng
        // CHỈ đánh dấu mã đơn hàng gốc (không có số thứ tự) và CHỈ đánh dấu đơn hàng được gửi sau (mới nhất)
        $hasOtherAgencyFlags = [];
        foreach ($requests as $req) {
            // Chỉ đánh dấu nếu order_code là mã gốc (không có số thứ tự)
            $isOriginalOrderCode = !preg_match('/\s*\(\d+\)$/', $req->order_code);
            
            if ($isOriginalOrderCode && $req->agency_id) {
                // Chỉ đánh dấu nếu request này là request mới nhất trong số các request có cùng mã đơn hàng gốc
                $hasOtherAgencyFlags[$req->id] = $this->isLatestRequestWithOtherAgency(
                    $req->order_code, 
                    $req->agency_id, 
                    $req->id, 
                    $req->created_at
                );
            } else {
                $hasOtherAgencyFlags[$req->id] = false;
            }
        }

        // Đếm số lượng theo trạng thái
        $counts = [
            'all' => RequestAgency::count(),
            'chua_xac_nhan_agency' => RequestAgency::where('status', RequestAgency::STATUS_CHUA_XAC_NHAN_AGENCY)->count(),
            'da_xac_nhan_agency' => RequestAgency::where('status', RequestAgency::STATUS_DA_XAC_NHAN_AGENCY)->count(),
            'da_dieu_phoi' => RequestAgency::where('status', RequestAgency::STATUS_DA_DIEU_PHOI)->count(),
            'hoan_thanh' => RequestAgency::where('status', RequestAgency::STATUS_HOAN_THANH)->count(),
            'da_thanh_toan' => RequestAgency::where('status', RequestAgency::STATUS_DA_THANH_TOAN)->count(),
        ];

        return view('requestagency.index', compact('requests', 'counts', 'hasOtherAgencyFlags'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('requestagency.create');
    }

    /**
     * Tạo order_code với số thứ tự nếu cùng đại lý và cùng mã đơn hàng gốc
     */
    private function generateOrderCodeWithSequence($originalOrderCode, $agencyId)
    {
        if (!$agencyId) {
            return $originalOrderCode;
        }

        // Tìm tất cả request của cùng đại lý với mã đơn hàng gốc
        // Tìm cả mã gốc và các mã có số thứ tự
        $existingRequests = RequestAgency::where('agency_id', $agencyId)
            ->where(function($query) use ($originalOrderCode) {
                $query->where('order_code', $originalOrderCode)
                      ->orWhere('order_code', 'like', $originalOrderCode . ' (%)');
            })
            ->get();

        // Nếu chưa có request nào, trả về mã gốc
        if ($existingRequests->isEmpty()) {
            return $originalOrderCode;
        }

        // Tìm số thứ tự lớn nhất
        $maxSequence = 0; // Bắt đầu từ 0
        foreach ($existingRequests as $req) {
            // Nếu order_code chính xác là mã gốc, đó là request đầu tiên (sequence = 1)
            if ($req->order_code === $originalOrderCode) {
                $maxSequence = max($maxSequence, 1);
            } else {
                // Kiểm tra pattern: "MÃ (số)"
                if (preg_match('/^' . preg_quote($originalOrderCode, '/') . '\s*\((\d+)\)$/', $req->order_code, $matches)) {
                    $maxSequence = max($maxSequence, (int)$matches[1]);
                }
            }
        }

        // Nếu đã có request với mã gốc, request tiếp theo sẽ là (2)
        // Nếu chưa có request với mã gốc nhưng có request với số thứ tự, tăng lên 1
        if ($maxSequence > 0) {
            $nextSequence = $maxSequence + 1;
            return $originalOrderCode . ' (' . $nextSequence . ')';
        }

        return $originalOrderCode;
    }

    /**
     * Kiểm tra xem request này có phải là request mới nhất trong số các request có cùng mã đơn hàng gốc nhưng khác đại lý
     * Chỉ đánh dấu request được gửi sau (mới hơn), không đánh dấu request đã có trước đó
     */
    private function isLatestRequestWithOtherAgency($orderCode, $currentAgencyId, $currentRequestId, $currentCreatedAt)
    {
        // Lấy mã đơn hàng gốc (bỏ số thứ tự nếu có)
        $originalOrderCode = preg_replace('/\s*\(\d+\)$/', '', $orderCode);

        // Tìm tất cả request có mã đơn hàng gốc (chỉ mã gốc, không có số thứ tự) và khác đại lý
        $otherAgencyRequests = RequestAgency::where('order_code', $originalOrderCode)
            ->where('agency_id', '!=', $currentAgencyId)
            ->whereNotNull('agency_id')
            ->get();

        // Nếu không có đại lý khác dùng mã này, không đánh dấu
        if ($otherAgencyRequests->isEmpty()) {
            return false;
        }

        // Tìm request mới nhất trong số các request có cùng mã đơn hàng gốc (bao gồm cả request hiện tại)
        $allRequestsWithSameCode = RequestAgency::where('order_code', $originalOrderCode)
            ->whereNotNull('agency_id')
            ->orderByDesc('created_at')
            ->first();

        // Chỉ đánh dấu nếu request hiện tại là request mới nhất
        return $allRequestsWithSameCode && $allRequestsWithSameCode->id == $currentRequestId;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_code' => 'required|string|max:100',
            'product_name' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'installation_address' => 'required|string',
            'notes' => 'nullable|string',
            'agency_name' => 'nullable|string|max:255',
            'agency_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Tìm agency_id từ agency_phone
        $agencyId = null;
        if ($request->agency_phone) {
            $agency = Agency::where('phone', $request->agency_phone)->first();
            if ($agency) {
                $agencyId = $agency->id;
            }
        }

        // Lấy mã đơn hàng gốc (bỏ số thứ tự nếu có)
        $originalOrderCode = preg_replace('/\s*\(\d+\)$/', '', $request->order_code);

        // Tạo order_code với số thứ tự nếu cùng đại lý
        $finalOrderCode = $this->generateOrderCodeWithSequence($originalOrderCode, $agencyId);

        RequestAgency::create([
            'order_code' => $finalOrderCode,
            'product_name' => $request->product_name,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'installation_address' => $request->installation_address,
            'notes' => $request->notes,
            'agency_name' => $request->agency_name,
            'agency_phone' => $request->agency_phone,
            'agency_id' => $agencyId,
            'status' => RequestAgency::STATUS_CHUA_XAC_NHAN_AGENCY,
        ]);

        return redirect()->route('requestagency.index')
            ->with('success', 'Tạo yêu cầu lắp đặt thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $request = RequestAgency::findOrFail($id);
        return view('requestagency.show', compact('request'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $request = RequestAgency::findOrFail($id);
        return view('requestagency.edit', compact('request'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $requestAgency = RequestAgency::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'order_code' => 'required|string|max:100',
            'product_name' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'installation_address' => 'required|string',
            'notes' => 'nullable|string',
            'status' => 'required|in:' . implode(',', array_keys(RequestAgency::getStatuses())),
            'agency_name' => 'nullable|string|max:255',
            'agency_phone' => 'nullable|string|max:20',
            'received_by' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Nếu chuyển sang trạng thái "đã xác nhận đại lý", tự động set received_at và received_by
        if ($request->status === RequestAgency::STATUS_DA_XAC_NHAN_AGENCY && !$requestAgency->received_at) {
            $requestAgency->received_at = now();
            $requestAgency->received_by = $request->received_by ?? session('user', 'system');
        }

        $requestAgency->update([
            'order_code' => $request->order_code,
            'product_name' => $request->product_name,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'installation_address' => $request->installation_address,
            'notes' => $request->notes,
            'status' => $request->status,
            'agency_name' => $request->agency_name,
            'agency_phone' => $request->agency_phone,
            'received_by' => $request->received_by ?? $requestAgency->received_by,
            'assigned_to' => $request->assigned_to,
        ]);

        return redirect()->route('requestagency.index')
            ->with('success', 'Cập nhật yêu cầu lắp đặt thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $request = RequestAgency::findOrFail($id);
        $request->delete();

        return redirect()->route('requestagency.index')
            ->with('success', 'Xóa yêu cầu lắp đặt thành công!');
    }

    /**
     * Cập nhật trạng thái nhanh
     */
    public function updateStatus(Request $request, string $id)
    {
        $requestAgency = RequestAgency::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', array_keys(RequestAgency::getStatuses())),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Trạng thái không hợp lệ'
            ], 422);
        }

        // Nếu chuyển sang trạng thái "đã xác nhận đại lý", tự động set received_at và received_by
        if ($request->status === RequestAgency::STATUS_DA_XAC_NHAN_AGENCY && !$requestAgency->received_at) {
            $requestAgency->received_at = now();
            $requestAgency->received_by = session('user', 'system');
        }

        $requestAgency->status = $request->status;
        $requestAgency->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!',
            'status_name' => $requestAgency->status_name
        ]);
    }
}
