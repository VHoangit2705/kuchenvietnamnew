<?php

namespace App\Http\Controllers;

use App\Models\KyThuat\RequestAgency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\Paginator;

Paginator::useBootstrap();

class RequestAgencyController extends Controller
{
    static $pageSize = 50;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
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

        // Đếm số lượng theo trạng thái
        $counts = [
            'all' => RequestAgency::count(),
            'chua_xac_nhan_agency' => RequestAgency::where('status', RequestAgency::STATUS_CHUA_XAC_NHAN_AGENCY)->count(),
            'chua_tiep_nhan' => RequestAgency::where('status', RequestAgency::STATUS_CHUA_TIEP_NHAN)->count(),
            'da_tiep_nhan' => RequestAgency::where('status', RequestAgency::STATUS_DA_TIEP_NHAN)->count(),
            'da_dieu_phoi' => RequestAgency::where('status', RequestAgency::STATUS_DA_DIEU_PHOI)->count(),
        ];

        return view('requestagency.index', compact('requests', 'counts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('requestagency.create');
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

        RequestAgency::create([
            'order_code' => $request->order_code,
            'product_name' => $request->product_name,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'installation_address' => $request->installation_address,
            'notes' => $request->notes,
            'agency_name' => $request->agency_name,
            'agency_phone' => $request->agency_phone,
            'status' => RequestAgency::STATUS_CHUA_TIEP_NHAN,
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

        // Nếu chuyển sang trạng thái "đã tiếp nhận", tự động set received_at và received_by
        if ($request->status === RequestAgency::STATUS_DA_TIEP_NHAN && !$requestAgency->received_at) {
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

        // Nếu chuyển sang trạng thái "đã tiếp nhận", tự động set received_at và received_by
        if ($request->status === RequestAgency::STATUS_DA_TIEP_NHAN && !$requestAgency->received_at) {
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
