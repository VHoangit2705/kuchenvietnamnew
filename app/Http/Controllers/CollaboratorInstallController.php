<?php

namespace App\Http\Controllers;

use App\Models\Kho\Order;
use App\Models\Kho\OrderProduct;
use App\Models\Kho\InstallationOrder;
use App\Models\KyThuat\Province;
use App\Models\KyThuat\District;
use App\Models\KyThuat\Wards;
use App\Models\KyThuat\WarrantyCollaborator;
use App\Models\KyThuat\WarrantyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Kho\Agency;
use App\Http\Controllers\SaveLogController;
use App\Models\KyThuat\EditCtvHistory;
use App\Models\KyThuat\RequestAgency;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class CollaboratorInstallController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:Xem danh sách CTV')->only(['Index']);
    //     $this->middleware('permission:Cập nhật CTV')->only(['CreateCollaborator', 'DeleteCollaborator']);
    // }
    static $pageSize = 50;
    protected $saveLogController;

    public function __construct()
    {
        // Chỉ khởi tạo SaveLogController, không còn sử dụng CTV flag "Đại lý lắp đặt"
        $this->saveLogController = new SaveLogController();
    }
    
    /**
     * Ghi log khi thay đổi CTV
     */
    private function logCollaboratorChange($oldCollaboratorId, $newCollaboratorId, $orderCode)
    {
        try {
            // Lấy thông tin CTV cũ và mới
            $oldCollaborator = $oldCollaboratorId ? WarrantyCollaborator::find($oldCollaboratorId) : null;
            $newCollaborator = $newCollaboratorId ? WarrantyCollaborator::find($newCollaboratorId) : null;

            if (!$oldCollaborator && !$newCollaborator) {
                return; 
            }

            // Tìm bản ghi lịch sử hiện có
            $existingHistory = EditCtvHistory::where('order_code', $orderCode)->first();

            if ($existingHistory) {
                // Nếu bản ghi trước đó đang lưu CTV thật (có collaborator_id) thì đẩy sang old_*
                if (!empty($existingHistory->new_collaborator_id)) {
                    // Nếu đang có CTV thật, đẩy dữ liệu CTV vào old_*
                    $existingHistory->old_collaborator_id = $existingHistory->new_collaborator_id;
                    $existingHistory->old_full_name = $existingHistory->new_full_name;
                    $existingHistory->old_phone = $existingHistory->new_phone;
                    $existingHistory->old_province = $existingHistory->new_province;
                    $existingHistory->old_province_id = $existingHistory->new_province_id;
                    $existingHistory->old_district = $existingHistory->new_district;
                    $existingHistory->old_district_id = $existingHistory->new_district_id;
                    $existingHistory->old_ward = $existingHistory->new_ward;
                    $existingHistory->old_ward_id = $existingHistory->new_ward_id;
                    $existingHistory->old_address = $existingHistory->new_address;
                    $existingHistory->old_sotaikhoan = $existingHistory->new_sotaikhoan;
                    $existingHistory->old_chinhanh = $existingHistory->new_chinhanh;
                    $existingHistory->old_cccd = $existingHistory->new_cccd;
                    $existingHistory->old_ngaycap = $existingHistory->new_ngaycap;
                }

                // Cập nhật new_collaborator_id
                $existingHistory->new_collaborator_id = $newCollaboratorId;

                // Lưu dữ liệu CTV mới vào new_*
                if ($newCollaborator) {
                    $existingHistory->new_full_name = $newCollaborator->full_name;
                    $existingHistory->new_phone = $newCollaborator->phone;
                    $existingHistory->new_province = $newCollaborator->province;
                    $existingHistory->new_province_id = $newCollaborator->province_id;
                    $existingHistory->new_district = $newCollaborator->district;
                    $existingHistory->new_district_id = $newCollaborator->district_id;
                    $existingHistory->new_ward = $newCollaborator->ward;
                    $existingHistory->new_ward_id = $newCollaborator->ward_id;
                    $existingHistory->new_address = $newCollaborator->address;
                    $existingHistory->new_sotaikhoan = $newCollaborator->sotaikhoan;
                    $existingHistory->new_chinhanh = $newCollaborator->chinhanh;
                    $existingHistory->new_cccd = $newCollaborator->cccd;
                    $existingHistory->new_ngaycap = $newCollaborator->ngaycap;
                } else {
                    // Nếu không có CTV mới (chuyển về đại lý) - chỉ set thông tin mô tả, không dùng ID = 1 làm flag nữa
                    $existingHistory->new_collaborator_id = null;
                    $existingHistory->new_full_name = 'Đại lý lắp đặt';
                    $existingHistory->new_phone = 'Đại lý lắp đặt';
                    $existingHistory->new_province = 'Đại lý lắp đặt';
                    $existingHistory->new_province_id = null;
                    $existingHistory->new_district = 'Đại lý lắp đặt';
                    $existingHistory->new_district_id = null;
                    $existingHistory->new_ward = 'Đại lý lắp đặt';
                    $existingHistory->new_ward_id = null;
                    $existingHistory->new_address = 'Đại lý lắp đặt';
                    $existingHistory->new_sotaikhoan = 'Đại lý lắp đặt';
                    $existingHistory->new_chinhanh = 'Đại lý lắp đặt';
                    $existingHistory->new_cccd = 'Đại lý lắp đặt';
                    $existingHistory->new_ngaycap = null;
                }

                $existingHistory->action_type = 'update';
                $existingHistory->edited_by = session('user', 'system');
                $existingHistory->edited_at = now();
                
                $oldName = $oldCollaborator ? $oldCollaborator->full_name : 'Đại lý lắp đặt';
                $newName = $newCollaborator ? $newCollaborator->full_name : 'Đại lý lắp đặt';
                $existingHistory->comments = "Thay đổi CTV: '{$oldName}' → '{$newName}'";

                $existingHistory->save();
            } else {
                // TẠO MỚI: Tạo bản ghi lịch sử mới
                $historyData = [
                    'old_collaborator_id' => $oldCollaboratorId,
                    'new_collaborator_id' => $newCollaboratorId,
                    'action_type' => 'update',
                    'edited_by' => session('user', 'system'),
                    'edited_at' => now(),
                    'order_code' => $orderCode
                ];

                // Lưu thông tin CTV cũ vào old_*
                if ($oldCollaborator) {
                    $historyData['old_full_name'] = $oldCollaborator->full_name;
                    $historyData['old_phone'] = $oldCollaborator->phone;
                    $historyData['old_province'] = $oldCollaborator->province;
                    $historyData['old_province_id'] = $oldCollaborator->province_id;
                    $historyData['old_district'] = $oldCollaborator->district;
                    $historyData['old_district_id'] = $oldCollaborator->district_id;
                    $historyData['old_ward'] = $oldCollaborator->ward;
                    $historyData['old_ward_id'] = $oldCollaborator->ward_id;
                    $historyData['old_address'] = $oldCollaborator->address;
                    $historyData['old_sotaikhoan'] = $oldCollaborator->sotaikhoan;
                    $historyData['old_chinhanh'] = $oldCollaborator->chinhanh;
                    $historyData['old_cccd'] = $oldCollaborator->cccd;
                    $historyData['old_ngaycap'] = $oldCollaborator->ngaycap;
                }

                // Lưu thông tin CTV mới vào new_*
                if ($newCollaborator) {
                    $historyData['new_full_name'] = $newCollaborator->full_name;
                    $historyData['new_phone'] = $newCollaborator->phone;
                    $historyData['new_province'] = $newCollaborator->province;
                    $historyData['new_province_id'] = $newCollaborator->province_id;
                    $historyData['new_district'] = $newCollaborator->district;
                    $historyData['new_district_id'] = $newCollaborator->district_id;
                    $historyData['new_ward'] = $newCollaborator->ward;
                    $historyData['new_ward_id'] = $newCollaborator->ward_id;
                    $historyData['new_address'] = $newCollaborator->address;
                    $historyData['new_sotaikhoan'] = $newCollaborator->sotaikhoan;
                    $historyData['new_chinhanh'] = $newCollaborator->chinhanh;
                    $historyData['new_cccd'] = $newCollaborator->cccd;
                    $historyData['new_ngaycap'] = $newCollaborator->ngaycap;
                }

                $oldName = $oldCollaborator ? $oldCollaborator->full_name : 'Đại lý lắp đặt';
                $newName = $newCollaborator ? $newCollaborator->full_name : 'Đại lý lắp đặt';
                $historyData['comments'] = "Thay đổi CTV: '{$oldName}' → '{$newName}'";

                EditCtvHistory::create($historyData);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi ghi log thay đổi CTV: ' . $e->getMessage());
        }
    }

    /**
     * Ghi log thay đổi trạng thái đơn hàng
     */
    private function logStatusChange($orderCode, $oldStatus, $newStatus, $action)
    {
        try {
            // Mapping trạng thái số sang text
            $statusMapping = [
                0 => 'Chưa điều phối',
                1 => 'Đã điều phối', 
                2 => 'Đã hoàn thành',
                3 => 'Đã thanh toán'
            ];
            
            $oldStatusText = $statusMapping[$oldStatus] ?? 'Không xác định';
            $newStatusText = $statusMapping[$newStatus] ?? 'Không xác định';
            
            // Tìm bản ghi hiện có để cập nhật
            $existingHistory = EditCtvHistory::where('order_code', $orderCode)->first();
            
            if ($existingHistory) {
                // Cập nhật bản ghi hiện có
                $existingHistory->action_type = $action;
                $existingHistory->edited_by = session('user', 'system');
                $existingHistory->edited_at = now();
                $existingHistory->comments = "Thay đổi trạng thái: {$oldStatusText} → {$newStatusText}";
                
                $existingHistory->save();
            } else {
                // Tạo bản ghi mới
                $newHistory = EditCtvHistory::create([
                    'order_code' => $orderCode,
                    'action_type' => $action,
                    'edited_by' => session('user', 'system'),
                    'edited_at' => now(),
                    'comments' => "Thay đổi trạng thái: {$oldStatusText} → {$newStatusText}"
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Lỗi ghi log thay đổi trạng thái: ' . $e->getMessage());
            return false;
        }
    }

    public function Index(Request $request)
    {
        $tab = 'donhang';
        // Trả về view và counts
        $counts = [
            'donhang' => 0,
            'dieuphoidonhangle' => 0,
            'dieuphoibaohanh' => 0,
            'dadieuphoi' => 0,
            'dailylapdat' => 0,
            'dahoanthanh' => 0,
            'dathanhtoan' => 0,
        ];
        return view('collaboratorinstall.index', compact('counts', 'tab'));
    }

    /**
     * Lấy dữ liệu cho tab cụ thể qua AJAX
     */
    public function getTabData(Request $request)
    {
        try {
            $tab = $request->get('tab', 'donhang');
            $view = session('brand') === 'hurom' ? 3 : 1;

            $mainQuery = $this->buildTabQuery($tab, $view);
            $mainQuery = $this->applyTabFilters($mainQuery, $tab, $request);
            if ($tab === 'dieuphoibaohanh') {
                $data = $mainQuery->orderByDesc('received_date')->orderByDesc('id')->paginate(50)->withQueryString();
            } elseif (in_array($tab, ['donhang', 'dieuphoidonhangle'])) {
                $data = $mainQuery->orderByDesc('orders.created_at')->orderByDesc('order_products.id')->paginate(50)->withQueryString();               
            } elseif ($tab === 'dadieuphoi') {
                // Sắp xếp theo dispatched_at (thời gian chuyển sang "Đã điều phối")
                $data = $mainQuery->orderByDesc('installation_orders.dispatched_at')
                    ->orderByDesc('installation_orders.created_at')
                    ->orderByDesc('installation_orders.id')
                    ->paginate(50)->withQueryString();
            } elseif ($tab === 'dailylapdat') {
                // Sắp xếp theo agency_at (thời gian chuyển sang "Đại lý lắp đặt")
                $data = $mainQuery->orderByDesc('installation_orders.agency_at')
                    ->orderByDesc('installation_orders.dispatched_at')
                    ->orderByDesc('installation_orders.created_at')
                    ->orderByDesc('installation_orders.id')
                    ->paginate(50)->withQueryString();
            } elseif ($tab === 'dahoanthanh') {
                // Sắp xếp theo successed_at (thời gian chuyển sang "Đã hoàn thành")
                $data = $mainQuery->orderByDesc('installation_orders.successed_at')
                    ->orderByDesc('installation_orders.id')
                    ->paginate(50)->withQueryString();
            } elseif ($tab === 'dathanhtoan') {
                // Sắp xếp theo paid_at (thời gian chuyển sang "Đã thanh toán")
                $data = $mainQuery->orderByDesc('installation_orders.paid_at')
                    ->orderByDesc('installation_orders.successed_at')
                    ->orderByDesc('installation_orders.id')
                    ->paginate(50)->withQueryString();
            } else {
                $data = $mainQuery->orderByDesc('installation_orders.created_at')->orderByDesc('installation_orders.id')->paginate(50)->withQueryString();
            }

            $startRender = microtime(true);
            $html = view('collaboratorinstall.tablecontent', compact('data', 'tab'))->render();
            $renderTime = round((microtime(true) - $startRender) * 1000, 2);
            
            // Chỉ trả về bảng dữ liệu; header (counts) sẽ được gọi riêng qua endpoint counts để giảm query
            return response()->json([
                'table' => $html,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage(),
                'table' => '<div class="alert alert-danger">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>'
            ], 500);
        }
    }

    /**
     * Build query cho từng tab
     */
    private function buildTabQuery($tab, $view)
    {
        try {
            // Định nghĩa closure trả về query cho từng tab
            $queryBuilders = [
            'donhang' => function () use ($view) {
                return OrderProduct::join('products as p', function($join) {
                        $join->on('order_products.product_name', '=', 'p.product_name');
                    })
                    ->join('orders', 'order_products.order_id', '=', 'orders.id')
                    ->leftJoin('installation_orders as io', function($join) {
                        $join->on(function($q) {
                            $q->whereColumn('io.order_code', 'orders.order_code2')
                              ->orWhereColumn('io.order_code', 'orders.order_code1');
                        })
                        ->whereColumn('io.product', 'order_products.product_name')
                        ->where('io.status_install', '>=', 1);
                    })
                    ->where('p.view', $view)
                    ->select(
                        'order_products.id',
                        'order_products.order_id',
                        'order_products.product_name',
                        'order_products.VAT',
                        'orders.order_code2',
                        'orders.zone',
                        'orders.created_at',
                        'orders.status_install',
                        'orders.agency_name',
                        'orders.agency_phone',
                        'orders.customer_name',
                        'orders.customer_phone',
                    )
                    ->where('order_products.install', 1)
                    ->where('orders.order_code2', 'not like', 'KU%')
                    ->where(function ($sub) {
                        $sub->whereNull('orders.status_install')
                            ->orWhere('orders.status_install', 0);
                    })
                    ->whereNull('orders.collaborator_id')
                    ->whereNull('io.id')
                    ->orderByDesc('orders.created_at');
            },
            'dieuphoidonhangle' => function () use ($view) {
                return OrderProduct::join('products as p', function($join) {
                        $join->on('order_products.product_name', '=', 'p.product_name');
                    })
                    ->join('orders', 'order_products.order_id', '=', 'orders.id')
                    ->leftJoin('installation_orders as io', function($join) {
                        $join->on(function($q) {
                            $q->whereColumn('io.order_code', 'orders.order_code2')
                              ->orWhereColumn('io.order_code', 'orders.order_code1');
                        })
                        ->whereColumn('io.product', 'order_products.product_name')
                        ->where('io.status_install', '>=', 1);
                    })
                    ->where('p.view', $view)
                    ->select(
                        'order_products.id',
                        'order_products.order_id',
                        'order_products.product_name',
                        'order_products.VAT',
                        'orders.order_code2',
                        'orders.zone',
                        'orders.created_at',
                        'orders.status_install',
                        'orders.agency_name',
                        'orders.agency_phone',
                        'orders.customer_name',
                        'orders.customer_phone',
                    )
                    ->where('order_products.install', 1)
                    ->where(function ($sub) {
                        $sub->whereNull('orders.status_install')
                            ->orWhere('orders.status_install', 0);
                    })
                    ->whereNull('orders.collaborator_id')
                    ->whereIn('orders.type', [
                        'warehouse_branch',
                        'warehouse_ghtk',
                        'warehouse_viettel'
                    ])
                    // Loại trừ các đơn đã có trong installation_orders với status_install >= 1 (đã điều phối)
                    ->whereNull('io.id')
                    ->orderByDesc('orders.created_at');
            },
            'dieuphoibaohanh' => function () use ($view) {
                return WarrantyRequest::where('type', 'agent_component')
                    ->where('view', $view);
            },
            // Dùng leftJoin để không mất bản ghi khi product_name không khớp bảng products
            // và vẫn ưu tiên lọc theo view nếu có.
            'dadieuphoi' => function () use ($view) {
                return InstallationOrder::leftJoin('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where(function($q) use ($view) {
                        $q->where('p.view', $view)->orWhereNull('p.view');
                    })
                    ->select('installation_orders.*')
                    ->where('installation_orders.status_install', 1)
                    // Đơn đã điều phối cho CTV (có collaborator_id, không phân loại theo flag ID=1 nữa)
                    ->whereNotNull('installation_orders.collaborator_id');
            },
            'dailylapdat' => function () use ($view) {
                return InstallationOrder::leftJoin('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where(function($q) use ($view) {
                        $q->where('p.view', $view)->orWhereNull('p.view');
                    })
                    ->select('installation_orders.*')
                    ->where('installation_orders.status_install', 1)
                    // Chỉ lấy đơn đã được tích checkbox "Đại lý lắp đặt" khi điều phối
                    // (có agency_name trong installation_orders và KHÔNG có collaborator_id)
                    ->where(function ($q) {
                        $q->whereNotNull('installation_orders.agency_name')
                          ->where('installation_orders.agency_name', '!=', '');
                    })
                    // Loại trừ đơn có CTV (nếu có collaborator_id thì không phải đại lý lắp đặt)
                    ->whereNull('installation_orders.collaborator_id');
            },
            'dahoanthanh' => function () use ($view) {
                return InstallationOrder::leftJoin('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where(function($q) use ($view) {
                        $q->where('p.view', $view)->orWhereNull('p.view');
                    })
                    ->select('installation_orders.*')
                    ->where('installation_orders.status_install', 2);
            },
            'dathanhtoan' => function () use ($view) {
                return InstallationOrder::leftJoin('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where(function($q) use ($view) {
                        $q->where('p.view', $view)->orWhereNull('p.view');
                    })
                    ->select('installation_orders.*')
                    ->where('installation_orders.status_install', 3);
            },
        ];
        
        $query = ($queryBuilders[$tab] ?? $queryBuilders['donhang'])();
        return $query;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Áp dụng filter động cho query
     */
    private function applyTabFilters($query, $tab, Request $request)
    {
        try {
            $tungay = $request->filled('tungay') ? (trim($request->input('tungay')) ?: null) : null;
            $denngay = $request->filled('denngay') ? (trim($request->input('denngay')) ?: null) : null;
            $madon = $request->filled('madon') ? (trim($request->input('madon')) ?: null) : null;
            $sanpham = $request->filled('sanpham') ? (trim($request->input('sanpham')) ?: null) : null;
            $trangthai = $request->filled('trangthai') && $request->input('trangthai') !== '' ? $request->input('trangthai') : null;
            $phanloai = $request->filled('phanloai') && $request->input('phanloai') !== '' ? $request->input('phanloai') : null;
            $customer_name = $request->filled('customer_name') ? (trim($request->input('customer_name')) ?: null) : null;
            $customer_phone = $request->filled('customer_phone') ? (trim($request->input('customer_phone')) ?: null) : null;
            $agency_phone = $request->filled('agency_phone') ? (trim($request->input('agency_phone')) ?: null) : null;
            $agency_name = $request->filled('agency_name') ? (trim($request->input('agency_name')) ?: null) : null;

        if (in_array($tab, ['donhang', 'dieuphoidonhangle'])) {
            $query->when(!empty($madon), function ($q) use ($madon) {
                $q->where('orders.order_code2', 'like', "%$madon%");
            })
            ->when(!empty($sanpham), function ($q) use ($sanpham) {
                // Chỉ định rõ table prefix để tránh xung đột khi có join nhiều bảng
                $q->where('order_products.product_name', 'like', "%$sanpham%");
            })
            ->when(!empty($tungay), function ($q) use ($tungay) {
                // Sử dụng trực tiếp trên bảng orders đã join thay vì whereHas để tối ưu hiệu năng
                $q->whereDate('orders.created_at', '>=', $tungay);
            })
            ->when(!empty($denngay), function ($q) use ($denngay) {
                // Sử dụng trực tiếp trên bảng orders đã join thay vì whereHas để tối ưu hiệu năng
                $q->whereDate('orders.created_at', '<=', $denngay);
            })
            ->when(!is_null($trangthai) && $trangthai !== '', function ($q) use ($trangthai) {
                if ($trangthai === '0') {
                    $q->where(function ($sub) {
                        $sub->whereNull('orders.status_install')->orWhere('orders.status_install', 0);
                    });
                } elseif ($trangthai === '1') {
                    $q->where('orders.status_install', 1);
                } elseif ($trangthai === '2') {
                    $q->where('orders.status_install', 2);
                } elseif ($trangthai === '3') {
                    $q->where('orders.status_install', 3);
                }
            })
            ->when(!is_null($phanloai) && $phanloai !== '', function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    // Đơn do CTV lắp đặt: không có thông tin đại lý
                    $q->where(function ($sub) {
                        $sub->whereNull('orders.agency_name')
                            ->orWhere('orders.agency_name', '');
                    });
                } elseif ($phanloai === 'agency') {
                    // Đơn do đại lý lắp đặt: có thông tin đại lý
                    $q->whereNotNull('orders.agency_name')
                      ->where('orders.agency_name', '!=', '');
                }
            })
            ->when(!empty($customer_name), function ($q) use ($customer_name) {
                $q->where('orders.customer_name', 'like', "%$customer_name%");
            })
            ->when(!empty($customer_phone), function ($q) use ($customer_phone) {
                $q->where('orders.customer_phone', 'like', "%$customer_phone%");
            })
            ->when(!empty($agency_phone), function ($q) use ($agency_phone) {
                $q->where('orders.agency_phone', 'like', "%$agency_phone%");
            })
            ->when(!empty($agency_name), function ($q) use ($agency_name) {
                $q->where('orders.agency_name', 'like', "%$agency_name%");
            });
        }
        // Filter cho WarrantyRequest (dieuphoibaohanh)
        elseif ($tab === 'dieuphoibaohanh') {
            $query->when(!empty($madon), fn($q) => $q->where('serial_number', 'like', "%$madon%"))
                ->when(!empty($sanpham), fn($q) => $q->where('product', 'like', "%$sanpham%"))
                // Lọc theo ngày nhận phiếu (received_date) để đồng bộ với cột "Ngày tạo" hiển thị ngoài view
                ->when(!empty($tungay), fn($q) => $q->whereDate('received_date', '>=', $tungay))
                ->when(!empty($denngay), function($q) use ($denngay) {
                    $q->whereDate('received_date', '<=', $denngay);
                })
                ->when(!is_null($trangthai) && $trangthai !== '', function ($q) use ($trangthai) {
                    if ($trangthai === '0') {
                        $q->where(function ($sub) {
                            $sub->whereNull('status_install')
                                ->orWhere('status_install', 0);
                        });
                    } elseif ($trangthai === '1') {
                        $q->where('status_install', 1);
                    } elseif ($trangthai === '2') {
                        $q->where('status_install', 2);
                    } elseif ($trangthai === '3') {
                        $q->where('status_install', 3);
                    }
                })
                ->when(!is_null($phanloai) && $phanloai !== '', function ($q) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        // Ca bảo hành do CTV phụ trách: không có thông tin đại lý
                        $q->where(function ($sub) {
                            $sub->whereNull('agency_name')
                                ->orWhere('agency_name', '');
                        });
                    } elseif ($phanloai === 'agency') {
                        // Ca bảo hành do đại lý phụ trách: có thông tin đại lý
                        $q->whereNotNull('agency_name')
                          ->where('agency_name', '!=', '');
                    }
                })
                ->when(!empty($customer_name), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
                ->when(!empty($customer_phone), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
                ->when(!empty($agency_name), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
                ->when(!empty($agency_phone), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"));
        }
        // Filter cho InstallationOrder (các tab còn lại)
        else {
            // Xác định trường thời gian để lọc theo tab
            $dateField = 'installation_orders.created_at'; // Mặc định
            if ($tab === 'dadieuphoi') {
                $dateField = 'installation_orders.dispatched_at';
            } elseif ($tab === 'dailylapdat') {
                $dateField = 'installation_orders.agency_at';
            } elseif ($tab === 'dahoanthanh') {
                $dateField = 'installation_orders.successed_at';
            } elseif ($tab === 'dathanhtoan') {
                $dateField = 'installation_orders.paid_at';
            }
            
            $query->when(!empty($madon), fn($q) => $q->where('installation_orders.order_code', 'like', "%$madon%"))
                ->when(!empty($sanpham), fn($q) => $q->where('installation_orders.product', 'like', "%$sanpham%"))
                ->when(!empty($tungay), function($q) use ($dateField, $tungay) {
                    $q->whereDate($dateField, '>=', $tungay);
                })
                ->when(!empty($denngay), function($q) use ($dateField, $denngay) {
                    $q->whereDate($dateField, '<=', $denngay);
                })
                ->when(!is_null($trangthai) && $trangthai !== '', function ($q) use ($trangthai) {
                    if ($trangthai === '0') {
                        $q->where(function ($sub) {
                            $sub->whereNull('installation_orders.status_install')
                                ->orWhere('installation_orders.status_install', 0);
                        });
                    } elseif ($trangthai === '1') {
                        $q->where('installation_orders.status_install', 1);
                    } elseif ($trangthai === '2') {
                        $q->where('installation_orders.status_install', 2);
                    } elseif ($trangthai === '3') {
                        $q->where('installation_orders.status_install', 3);
                    }
                })
                ->when(!is_null($phanloai) && $phanloai !== '', function ($q) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        // Lắp đặt do CTV phụ trách: không có thông tin đại lý
                        $q->where(function ($sub) {
                            $sub->whereNull('installation_orders.agency_name')
                                ->orWhere('installation_orders.agency_name', '');
                        });
                    } elseif ($phanloai === 'agency') {
                        // Lắp đặt do đại lý phụ trách: có thông tin đại lý
                        $q->whereNotNull('installation_orders.agency_name')
                          ->where('installation_orders.agency_name', '!=', '');
                    }
                })
                ->when(!empty($customer_name), fn($q) => $q->where('installation_orders.full_name', 'like', "%$customer_name%"))
                ->when(!empty($customer_phone), fn($q) => $q->where('installation_orders.phone_number', 'like', "%$customer_phone%"))
                ->when(!empty($agency_name), fn($q) => $q->where('installation_orders.agency_name', 'like', "%$agency_name%"))
                ->when(!empty($agency_phone), fn($q) => $q->where('installation_orders.agency_phone', 'like', "%$agency_phone%"));
        }
            return $query;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function Details(Request $request)
    {
        try {
            $installationOrder = null;
            $order = null;
            $orderCode = null;
            $statusInstall = null;
            $productName = null;
            
            if ($request->type == 'donhang') {
                $data = OrderProduct::with('order')->findOrFail($request->id);
                $order = $data->order;
                $productName = $data->product_name ?? null;
                $orderCode = $order->order_code2 ?? $order->order_code1 ?? null;
                $installationOrder = null;
            
            // Luôn tìm installation_orders trước (ưu tiên dữ liệu đã điều phối)
            if ($orderCode) {
                $installationOrder = InstallationOrder::where('order_code', $orderCode)
                    ->when($productName, fn($q) => $q->where('product', $productName))
                    ->first();
                if ($installationOrder) {
                    // Nếu có installation_orders, ưu tiên dùng nó (đã điều phối)
                    $data = $installationOrder;
                    $statusInstall = $installationOrder->status_install ?? null;
                    $productName = $installationOrder->product ?? $productName;
                } else {
                    // Chưa có installation_orders, dùng order gốc
                    $statusInstall = $order->status_install ?? null;
                }
            } else {
                $statusInstall = $order->status_install ?? null;
            }
            
            $provinceId = $installationOrder ? ($installationOrder->province_id ?? $order->province ?? null) : ($order->province ?? null);
            $districtId = $installationOrder ? ($installationOrder->district_id ?? $order->district ?? null) : ($order->district ?? null);
            $wardId     = $installationOrder ? ($installationOrder->ward_id ?? $order->wards ?? null) : ($order->wards ?? null);
            $agency_phone = $installationOrder ? ($installationOrder->agency_phone ?? $order->agency_phone) : $order->agency_phone;
            
        } else if ($request->type == 'baohanh') {
            $data = WarrantyRequest::findOrFail($request->id);
            $orderCode = $data->serial_number ?? null;
            $installationOrder = null; // Khởi tạo biến
            
            // Luôn tìm installation_orders trước (ưu tiên dữ liệu đã điều phối)
            if ($orderCode) {
                $installationOrder = InstallationOrder::where('order_code', $orderCode)->first();
                if ($installationOrder) {
                    // Nếu có installation_orders, ưu tiên dùng nó (đã điều phối)
                    $data = $installationOrder;
                    $statusInstall = $installationOrder->status_install ?? null;
                } else {
                    // Chưa có installation_orders, dùng warranty_request gốc
                    $statusInstall = $data->status_install ?? null;
                }
            } else {
                $statusInstall = $data->status_install ?? null;
            }
            
            $provinceId = $installationOrder ? ($installationOrder->province_id ?? $data->province_id ?? null) : ($data->province_id ?? null);
            $districtId = $installationOrder ? ($installationOrder->district_id ?? $data->district_id ?? null) : ($data->district_id ?? null);
            $wardId     = $installationOrder ? ($installationOrder->ward_id ?? $data->ward_id ?? null) : ($data->ward_id ?? null);
            $agency_phone = $installationOrder ? ($installationOrder->agency_phone ?? $data->agency_phone) : $data->agency_phone;
            
        } else {
            // type = danhsach hoặc default
            $data = InstallationOrder::find($request->id);
            
            if ($data) {
                // Tìm thấy InstallationOrder
                $installationOrder = $data;
                $orderCode = $data->order_code ?? null;
                $statusInstall = $data->status_install ?? null;
                $order = null;
            } else {
                // Không tìm thấy InstallationOrder, tìm OrderProduct
                $orderProduct = OrderProduct::with('order')->find($request->id);
                if ($orderProduct) {
                    $data = $orderProduct;
                    $order = $orderProduct->order;
                    $productName = $orderProduct->product_name ?? null;
                    $orderCode = $order->order_code2 ?? $order->order_code1 ?? null;
                    $statusInstall = $order->status_install ?? null;
                    $installationOrder = null;
                    
                    // Tìm installation_orders nếu có order_code (có thể đã được điều phối sau khi tạo link)
                    if ($orderCode) {
                        $installationOrder = InstallationOrder::where('order_code', $orderCode)
                            ->when($productName, fn($q) => $q->where('product', $productName))
                            ->first();
                        if ($installationOrder) {
                            $data = $installationOrder;
                            $statusInstall = $installationOrder->status_install ?? null;
                        }
                    }
                } else {
                    throw new ModelNotFoundException("No query results for model [App\Models\Kho\InstallationOrder] {$request->id}");
                }
            }
            
            // Nếu có order_code, tìm order để fallback
            if ($orderCode && !isset($order)) {
                $order = Order::where('order_code2', $orderCode)
                    ->orWhere('order_code1', $orderCode)
                    ->first();
            }
            
            // Nếu status_install = 0 hoặc null, ưu tiên dùng order thay vì installationOrder
            if (($statusInstall === null || $statusInstall == 0) && isset($order) && $order) {
                $data = $order;
                $installationOrder = null;
            }
            
            $provinceId = $installationOrder ? ($installationOrder->province_id ?? null) : ($order->province ?? null);
            $districtId = $installationOrder ? ($installationOrder->district_id ?? null) : ($order->district ?? null);
            $wardId     = $installationOrder ? ($installationOrder->ward_id ?? null) : ($order->wards ?? null);
            $agency_phone = $installationOrder ? $installationOrder->agency_phone : ($order->agency_phone ?? null);
        }
        
        // FLOW XỬ LÝ REQUEST_AGENCY:
        // 1. Đại lý gửi form → tạo request_agency với status = "chua_xac_nhan_daily"
        // 2. User xác nhận đại lý → cập nhật status = "da_xac_nhan_daily"
        // 3. User điều phối tra mã đơn hàng → kiểm tra có request_agency không
        // 4. Nếu có và tích "Đại lý lắp đặt" → tự động điền thông tin và đồng bộ status với status_install
        
        // Kiểm tra xem có yêu cầu đại lý (request_agency) với mã đơn hàng này không
        $requestAgency = null;
        $requestAgencyAgency = null; // Agency lấy theo agency_id trong request_agency
        if ($orderCode) {
            // Lấy request_agency với các trạng thái: chua_xac_nhan_daily, da_xac_nhan_daily (chưa điều phối)
            $requestAgency = RequestAgency::where('order_code', $orderCode)
                ->when($productName, function($q) use ($productName) {
                    // Chỉ lấy yêu cầu khớp sản phẩm; vẫn cho phép bản ghi product_name null 
                    $q->where(function($sub) use ($productName) {
                        $sub->whereNull('product_name')
                            ->orWhere('product_name', $productName);
                    });
                })
                ->whereIn('status', [
                    RequestAgency::STATUS_CHUA_XAC_NHAN_AGENCY,
                    RequestAgency::STATUS_DA_XAC_NHAN_AGENCY
                ])
                ->orderByDesc('created_at') // Lấy bản ghi mới nhất nếu có nhiều
                ->first();
            
            // Nếu có request_agency đã tiếp nhận, ưu tiên lấy thông tin từ đó
            if ($requestAgency) {
                // Lấy thông tin agency theo agency_id (database kho_kuchen - connection mysql3)
                if (!empty($requestAgency->agency_id)) {
                    $requestAgencyAgency = Agency::find($requestAgency->agency_id);
                }

                // Ưu tiên thông tin từ request_agency cho đại lý
                if (empty($agency_phone) && $requestAgencyAgency?->phone) {
                    $agency_phone = $requestAgencyAgency->phone;
                }
                
                // Nếu chưa có địa chỉ lắp đặt chi tiết, lấy từ request_agency
                if ($installationOrder && empty($installationOrder->address)) {
                    $installationOrder->address = $requestAgency->installation_address;
                }
            }
        }
        
        // Tìm agency: ưu tiên theo agency_id trong installation_orders, sau đó theo phone
        $agency = null;
        
        // Ưu tiên 1: Tìm theo agency_id trong installation_orders
        if ($installationOrder && $installationOrder->agency_id) {
            $agency = Agency::find($installationOrder->agency_id);
        }
        
        // Ưu tiên 2: Tìm theo phone (normalize phone number)
        if (!$agency && $agency_phone) {
            // Normalize phone number (loại bỏ khoảng trắng và ký tự đặc biệt)
            $normalizedPhone = preg_replace('/[^0-9]/', '', $agency_phone);
            
            // Tìm chính xác
            $agency = Agency::where('phone', $normalizedPhone)->first();
            
            // Nếu không tìm thấy, thử tìm với LIKE (trường hợp có khoảng trắng hoặc format khác)
            if (!$agency && !empty($normalizedPhone)) {
                $agency = Agency::where('phone', 'like', '%' . $normalizedPhone . '%')->first();
            }
            
            // Nếu vẫn không tìm thấy, thử tìm với phone gốc (không normalize)
            if (!$agency) {
                $agency = Agency::where('phone', $agency_phone)->first();
            }
        }
        
        // Nếu có agency từ request_agency, ưu tiên dùng nó
        if (!$agency && isset($requestAgencyAgency) && $requestAgencyAgency) {
            $agency = $requestAgencyAgency;
        }
        
        $provinces = Province::orderBy('name')->get();
        
        $provinceName = $provinceId ? Province::find($provinceId)?->name : null;
        $districtName = $districtId ? District::find($districtId)?->name : null;
        $wardName     = $wardId ? Wards::find($wardId)?->name : null;
        $fullAddress = implode(', ', array_filter([$wardName, $districtName, $provinceName]));
        
        $lstCollaborator = WarrantyCollaborator::query()
            // Lấy tất cả CTV thật, không còn dùng ID = 1 làm flag đại lý
            ->when($provinceId, function ($q) use ($provinceId) {
                $q->where('province_id', $provinceId);
            })
            ->select('*')
            ->selectRaw('
                (CASE WHEN province_id = ? THEN 1 ELSE 0 END +
                 CASE WHEN district_id = ? THEN 1 ELSE 0 END +
                 CASE WHEN ward_id = ? THEN 1 ELSE 0 END) as match_score
            ', [$provinceId, $districtId, $wardId])
            ->orderByDesc('match_score')
            ->limit(10)
            ->get();

        return view('collaboratorinstall.details', compact(
            'data',
            'lstCollaborator',
            'provinces',
            'agency',
            'fullAddress',
            'provinceName',
            'districtName',
            'wardName',
            'provinceId',
            'districtId',
            'wardId',
            'installationOrder',
            'order',
            'orderCode',
            'statusInstall',
            'requestAgency',
            'requestAgencyAgency'
        ));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function Update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'installcost' => 'nullable|numeric|min:0',
            'ctv_id' => 'nullable',
            'successed_at' => 'nullable',
            'installreview' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            // Thông tin đại lý (khi thao tác “Đại lý lắp đặt” ở cơ chế riêng)
            'agency_name' => 'nullable|string|max:255',
            'agency_phone' => 'nullable|string|max:50',
            'agency_address' => 'nullable|string|max:500',
            'agency_bank' => 'nullable|string|max:255',
            'agency_paynumber' => 'nullable|string|max:100',
            'agency_branch' => 'nullable|string|max:255',
            'agency_cccd' => 'nullable|string|max:20',
            'agency_release_date' => 'nullable|date',
            'bank_account' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Tìm InstallationOrder hoặc Order/WarrantyRequest để đọc dữ liệu (KHÔNG sửa bảng orders/warranty_requests)
            $installationOrder = InstallationOrder::find($request->id);
            $sourceOrder = null;
            $sourceWarrantyRequest = null;
            $sourceOrderProduct = null;
            $orderCode = null;
            $productName = null;
            
            // Nếu chưa có InstallationOrder, tìm Order hoặc WarrantyRequest để đọc dữ liệu
            if (!$installationOrder) {
                $sourceModel = match ($request->type) {
                    'donhang'  => Order::findOrFail($request->id),
                    'baohanh'  => WarrantyRequest::findOrFail($request->id),
                    default    => InstallationOrder::findOrFail($request->id),
                };
                
                // Lưu reference để đọc dữ liệu sau này (KHÔNG sửa)
                if ($sourceModel instanceof Order) {
                    $sourceOrder = $sourceModel;
                    $orderCode = $sourceOrder->order_code2 ?? $sourceOrder->order_code1;
                    // Thử xác định sản phẩm cần lắp đặt từ OrderProduct nếu có
                    if ($request->has('product_id')) {
                        $sourceOrderProduct = OrderProduct::find($request->product_id);
                        $productName = $sourceOrderProduct?->product_name;
                    } else {
                        $productName = $request->product 
                            ?? OrderProduct::where('order_id', $sourceOrder->id)
                                ->where('install', 1)
                                ->value('product_name');
                    }
                } elseif ($sourceModel instanceof WarrantyRequest) {
                    $sourceWarrantyRequest = $sourceModel;
                    $orderCode = $sourceWarrantyRequest->serial_number;
                    $productName = $sourceWarrantyRequest->product ?? null;
                } else {
                    $installationOrder = $sourceModel;
                    $orderCode = $installationOrder->order_code ?? null;
                    $productName = $installationOrder->product ?? null;
                }
            } else {
                $orderCode = $installationOrder->order_code ?? null;
                $productName = $installationOrder->product ?? null;
            }
            
            // Nếu có orderCode nhưng chưa có InstallationOrder, tìm Order để đọc dữ liệu
            if (!$installationOrder && $orderCode) {
                $sourceOrder = Order::where('order_code2', $orderCode)
                    ->orWhere('order_code1', $orderCode)
                    ->first();
                if ($sourceOrder && !$productName) {
                    $productName = $request->product 
                        ?? OrderProduct::where('order_id', $sourceOrder->id)
                            ->where('install', 1)
                            ->value('product_name');
                }
            }

            // Lấy trạng thái hiện tại từ InstallationOrder (nếu có) hoặc từ source
            $currentStatusInstall = $installationOrder?->status_install 
                ?? $sourceOrder?->status_install 
                ?? $sourceWarrantyRequest?->status_install 
                ?? null;
            
            // Kiểm tra trạng thái đơn hàng
            $isPaidOrder = ($currentStatusInstall === 3);
            $action = $request->input('action');
            
            // Chỉ chặn nếu cố gắng thay đổi trạng thái từ "Đã thanh toán" (3)
            // Cho phép 'update' để cập nhật thông tin, chỉ chặn 'complete' và 'payment'
            if ($isPaidOrder && in_array($action, ['complete', 'payment'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng đã thanh toán, không thể thay đổi trạng thái! Bạn chỉ có thể cập nhật thông tin khác.',
                    'error_code' => 'PAID_ORDER_STATUS_LOCKED'
                ], 403);
            }

            if (in_array($action, ['complete', 'payment']) && !$request->successed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yêu cầu nhập ngày hoàn thành!'
                ]);
            }
            
            // Lưu collaborator_id cũ và trạng thái cũ để so sánh (TRƯỚC khi thay đổi)
            $oldCollaboratorId = $installationOrder?->collaborator_id ?? null;
            $oldStatus = $currentStatusInstall;
            
            // Validate và normalize collaborator_id (không còn dùng ID = 1 làm flag)
            $ctvId = $request->ctv_id ?: null;

            // Nếu là đại lý (cơ chế riêng), nhận thông tin đại lý từ request để lưu xuống đơn
            $agencyName = $request->input('agency_name');
            $agencyPhone = $request->input('agency_phone');
            $agencyAddress = $request->input('agency_address');
            $agencyBank = $request->input('agency_bank');
            $agencyPaynumber = $request->input('agency_paynumber');
            $agencyBranch = $request->input('agency_branch');
            $agencyCccd = $request->input('agency_cccd');
            $agencyReleaseDate = $request->input('agency_release_date');
            $bankAccount = $request->input('bank_account');

            // Xác định đang dùng CTV hay Đại lý làm đơn vị lắp đặt
            // Nếu đã chọn CTV thì xem như ca CTV, KHÔNG được set thông tin đại lý nữa
            $isCollaboratorInstall = !empty($ctvId);
            $isAgencyInstall = !$isCollaboratorInstall && (!empty($agencyName) || !empty($agencyPhone));
            
            // Tính toán trạng thái mới (KHÔNG sửa vào model gốc)
            $newStatusInstall = $currentStatusInstall;
            if (!$isPaidOrder) {
                // Xử lý theo action nếu có
                if ($action) {
                    switch ($action) {
                        case 'update':
                            $newStatusInstall = 1;
                            break;

                        case 'complete':
                            $newStatusInstall = 2;
                            break;

                        case 'payment':
                            $newStatusInstall = 3;
                            break;
                    }
                } else {
                    // Nếu không có action nhưng đang chỉ định CTV hoặc Đại lý lắp đặt
                    // và đơn chưa điều phối (status_install = 0 hoặc null), tự động chuyển sang "Đã điều phối"
                    if (($isCollaboratorInstall || $isAgencyInstall) && 
                        ($currentStatusInstall === null || $currentStatusInstall == 0)) {
                        $newStatusInstall = 1; // Đã điều phối
                    }
                }
            }

            // Xử lý file review (nếu có)
            $reviewsInstallFilename = $installationOrder?->reviews_install ?? null;
            if ($request->hasFile('installreview')) {
                $file = $request->file('installreview');
                $extension = strtolower($file->getClientOriginalExtension());

                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.pdf';
                $savePath = storage_path('app/public/install_reviews/' . $filename);

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    // Convert ảnh -> PDF
                    $imgData = base64_encode(file_get_contents($file->getRealPath()));
                    $html = '<img src="data:image/' . $extension . ';base64,' . $imgData . '" style="width:100%;">';
                    $pdf = Pdf::loadHTML($html);
                    $pdf->save($savePath);
                } elseif ($extension === 'pdf') {
                    // Giữ nguyên PDF
                    $file->storeAs('install_reviews', $filename, 'public');
                }

                $reviewsInstallFilename = $filename;
            }

            // Đảm bảo có orderCode để tiếp tục xử lý
            if (empty($orderCode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy mã đơn hàng!'
                ], 400);
            }

            // Đảm bảo có productName để tách theo từng sản phẩm lắp đặt
            if (empty($productName)) {
                $productName = $request->product
                    ?? $requestAgency?->product_name
                    ?? $sourceOrder?->product_name
                    ?? $sourceWarrantyRequest?->product
                    ?? $installationOrder?->product
                    ?? null;
            }

            // Lấy thông tin từ request_agency nếu có
            $requestAgency = RequestAgency::where('order_code', $orderCode)
                ->when($productName, function($q) use ($productName) {
                    $q->where(function($sub) use ($productName) {
                        $sub->whereNull('product_name')
                            ->orWhere('product_name', $productName);
                    });
                })
                ->first();
            $requestAgencyAgency = null;
            if ($requestAgency && $requestAgency->agency_id) {
                $requestAgencyAgency = Agency::find($requestAgency->agency_id);
            }
            
            // Xác định thông tin đại lý cho installation_orders:
            // Ưu tiên: request->input (form) > Agency từ request_agency > orders.agency_name/agency_phone (nếu chưa có RequestAgency)
            // CHỈ khi là ca đại lý lắp đặt
            if ($isAgencyInstall) {
                $resolvedAgencyName = $agencyName 
                    ?: ($requestAgencyAgency->name ?? null)
                    ?: ($sourceOrder?->agency_name ?? null)
                    ?: '';
                $resolvedAgencyPhone = $agencyPhone 
                    ?: ($requestAgencyAgency->phone ?? null)
                    ?: ($sourceOrder?->agency_phone ?? null)
                    ?: '';

                // Thêm: xác định agency_id để lưu xuống installation_orders
                // Ưu tiên: request_agency.agency_id > Agency tìm theo phone > Agency tìm theo name > Tạo mới
                $resolvedAgencyId = null;
                $agency = null;
                
                // Normalize phone number (loại bỏ khoảng trắng và ký tự đặc biệt)
                $normalizedPhone = !empty($resolvedAgencyPhone) ? preg_replace('/[^0-9]/', '', $resolvedAgencyPhone) : '';
                
                if ($requestAgency && $requestAgency->agency_id) {
                    $agency = Agency::find($requestAgency->agency_id);
                    if ($agency) {
                        $resolvedAgencyId = $agency->id;
                    }
                }
                
                // Nếu chưa có agency, tìm theo số điện thoại (normalized)
                if (!$agency && !empty($normalizedPhone)) {
                    // Tìm chính xác
                    $agency = Agency::where('phone', $normalizedPhone)->first();
                    
                    // Nếu không tìm thấy, thử tìm với LIKE (trường hợp có khoảng trắng)
                    if (!$agency) {
                        $agency = Agency::where('phone', 'like', '%' . $normalizedPhone . '%')->first();
                    }
                    
                    if ($agency) {
                        $resolvedAgencyId = $agency->id;
                        // Cập nhật phone về dạng normalized nếu khác
                        if ($agency->phone !== $normalizedPhone) {
                            $agency->phone = $normalizedPhone;
                            $agency->save();
                        }
                    }
                }
                
                // Nếu vẫn chưa có, thử tìm theo CCCD (nếu có)
                if (!$agency && !empty($agencyCccd)) {
                    $agency = Agency::where('cccd', $agencyCccd)->first();
                    if ($agency) {
                        $resolvedAgencyId = $agency->id;
                    }
                }
                
                // Nếu vẫn chưa có agency và có số điện thoại, tạo mới hoặc cập nhật
                if (!empty($normalizedPhone)) {
                    try {
                        // Sử dụng normalized phone
                        $resolvedAgencyPhone = $normalizedPhone;
                        
                        // Chuẩn bị dữ liệu agency để updateOrCreate
                        // Ưu tiên dữ liệu từ request, nếu không có thì dùng dữ liệu hiện có (nếu có agency)
                        $agencyData = [
                            'name' => $resolvedAgencyName ?: ($agency?->name ?? ''),
                            'phone' => $normalizedPhone,
                            'address' => $agencyAddress ?: ($agency?->address ?? ''),
                            'bank_name_agency' => $agencyBank ?: ($agency?->bank_name_agency ?? ''),
                            'bank_account' => $bankAccount ?: ($agency?->bank_account ?? ''),
                            'sotaikhoan' => $agencyPaynumber ?: ($agency?->sotaikhoan ?? ''),
                            'chinhanh' => $agencyBranch ?: ($agency?->chinhanh ?? ''),
                            'cccd' => $agencyCccd ?: ($agency?->cccd ?? ''),
                            'ngaycap' => $agencyReleaseDate ?: ($agency?->ngaycap ?? null),
                            'create_by' => session('user', 'system'),
                        ];
                        
                        if ($agency) {
                            // Nếu đã có agency, chỉ cập nhật các trường không rỗng từ request
                            $updateData = [];
                            if (!empty($resolvedAgencyName)) {
                                $updateData['name'] = $resolvedAgencyName;
                            }
                            if (!empty($agencyAddress)) {
                                $updateData['address'] = $agencyAddress;
                            }
                            if (!empty($agencyBank)) {
                                $updateData['bank_name_agency'] = $agencyBank;
                            }
                            if (!empty($bankAccount)) {
                                $updateData['bank_account'] = $bankAccount;
                            }
                            if (!empty($agencyPaynumber)) {
                                $updateData['sotaikhoan'] = $agencyPaynumber;
                            }
                            if (!empty($agencyBranch)) {
                                $updateData['chinhanh'] = $agencyBranch;
                            }
                            if (!empty($agencyCccd)) {
                                $updateData['cccd'] = $agencyCccd;
                            }
                            if (!empty($agencyReleaseDate)) {
                                $updateData['ngaycap'] = $agencyReleaseDate;
                            }
                            
                            if (!empty($updateData)) {
                                $agency->update($updateData);
                            }
                        } else {
                            // Nếu chưa có agency, sử dụng updateOrCreate để tránh duplicate
                            // Tìm theo phone trước, nếu không có thì tạo mới
                            $agencyData['created_ad'] = now();
                            
                            // Đảm bảo bank_account có giá trị (không được null nếu field không có default)
                            if (empty($agencyData['bank_account'])) {
                                $agencyData['bank_account'] = '';
                            }
                            
                            // Chuyển đổi các giá trị rỗng thành null để database xử lý (trừ bank_account)
                            foreach ($agencyData as $key => $value) {
                                if ($value === '' && $key !== 'bank_account') {
                                    $agencyData[$key] = null;
                                }
                            }
                            
                            $agency = Agency::updateOrCreate(
                                ['phone' => $normalizedPhone],
                                $agencyData
                            );
                        }
                        
                        $resolvedAgencyId = $agency->id;
                        
                        // Log để debug
                        Log::info('Agency đã được tạo/cập nhật thành công', [
                            'agency_id' => $resolvedAgencyId,
                            'phone' => $normalizedPhone,
                            'name' => $resolvedAgencyName
                        ]);
                    } catch (\Exception $e) {
                        // Log lỗi chi tiết
                        Log::error('Lỗi khi tạo/cập nhật agency: ' . $e->getMessage(), [
                            'phone' => $normalizedPhone ?? $resolvedAgencyPhone,
                            'name' => $resolvedAgencyName,
                            'agency_data' => $agencyData ?? [],
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        
                        // Nếu có lỗi nhưng đã có agency_id từ trước, vẫn tiếp tục
                        if (!$resolvedAgencyId && $agency) {
                            $resolvedAgencyId = $agency->id;
                        }
                        
                        // Nếu vẫn chưa có agency_id, thử tìm lại một lần nữa
                        if (!$resolvedAgencyId && !empty($normalizedPhone)) {
                            $existingAgency = Agency::where('phone', $normalizedPhone)->first();
                            if ($existingAgency) {
                                $resolvedAgencyId = $existingAgency->id;
                                $agency = $existingAgency;
                            }
                        }
                    }
                } else {
                    // Nếu không có số điện thoại nhưng đã có agency_id từ trước, giữ nguyên
                    if ($installationOrder && $installationOrder->agency_id) {
                        $resolvedAgencyId = $installationOrder->agency_id;
                    }
                }
            } else {
                // Ca CTV: KHÔNG lưu thông tin đại lý
                $resolvedAgencyName = '';
                $resolvedAgencyPhone = '';
                $resolvedAgencyId = null;
            }

            // Xử lý các trường thời gian khi thay đổi trạng thái
            $now = now();
            $dispatchedAt = $installationOrder?->dispatched_at;
            $successedAt = $installationOrder?->successed_at;
            $paidAt = $installationOrder?->paid_at;
            $agencyAt = $installationOrder?->agency_at;
            
            // Kiểm tra xem có chuyển sang đại lý lắp đặt không (có agency_name và không có collaborator_id)
            $wasAgencyInstall = !empty($installationOrder?->agency_name) && empty($installationOrder?->collaborator_id);
            $isNowAgencyInstall = $isAgencyInstall && !empty($resolvedAgencyName);
            
            // Set dispatched_at khi chuyển sang status_install = 1 (Đã điều phối)
            if ($newStatusInstall == 1 && $oldStatus != 1) {
                $dispatchedAt = $now;
            } elseif ($newStatusInstall == 1 && $oldStatus == 1 && !$dispatchedAt) {
                // Nếu đã là status 1 nhưng chưa có dispatched_at, set luôn
                $dispatchedAt = $now;
            }
            
            // Set successed_at khi chuyển sang status_install = 2 (Đã hoàn thành)
            if ($newStatusInstall == 2 && $oldStatus != 2) {
                $successedAt = $request->successed_at ?? $now;
            }
            
            // Set paid_at khi chuyển sang status_install = 3 (Đã thanh toán)
            if ($newStatusInstall == 3 && $oldStatus != 3) {
                $paidAt = $now;
            }
            
            // Set agency_at khi chuyển sang "Đại lý lắp đặt" (có agency_name và không có collaborator_id)
            if ($isNowAgencyInstall && !$wasAgencyInstall) {
                $agencyAt = $now;
            } elseif ($isNowAgencyInstall && $wasAgencyInstall && !$agencyAt) {
                // Nếu đã là đại lý nhưng chưa có agency_at, set luôn
                $agencyAt = $now;
            }

            // Chuẩn bị dữ liệu cho installation_orders (KHÔNG động vào orders/warranty_requests)
            $installationOrderData = [
                'full_name'        => $requestAgency?->customer_name 
                    ?? $sourceOrder?->customer_name 
                    ?? $sourceWarrantyRequest?->full_name 
                    ?? $installationOrder?->full_name 
                    ?? '',
                'phone_number'     => $requestAgency?->customer_phone 
                    ?? $sourceOrder?->customer_phone 
                    ?? $sourceWarrantyRequest?->phone_number 
                    ?? $installationOrder?->phone_number 
                    ?? '',
                'address'          => $requestAgency?->installation_address 
                    ?? $sourceOrder?->customer_address 
                    ?? $sourceWarrantyRequest?->address 
                    ?? $installationOrder?->address 
                    ?? '',
                'product'          => $productName 
                    ?? $request->product 
                    ?? $requestAgency?->product_name 
                    ?? $sourceOrder?->product_name 
                    ?? $sourceWarrantyRequest?->product 
                    ?? $installationOrder?->product 
                    ?? '',
                'province_id'      => $sourceOrder?->province 
                    ?? $sourceWarrantyRequest?->province_id 
                    ?? $installationOrder?->province_id 
                    ?? null,
                'district_id'      => $sourceOrder?->district 
                    ?? $sourceWarrantyRequest?->district_id 
                    ?? $installationOrder?->district_id 
                    ?? null,
                'ward_id'          => $sourceOrder?->wards 
                    ?? $sourceWarrantyRequest?->ward_id 
                    ?? $installationOrder?->ward_id 
                    ?? null,
                'collaborator_id'  => $isCollaboratorInstall ? $ctvId : null,
                'install_cost'     => $request->installcost ?? $installationOrder?->install_cost ?? null,
                'status_install'   => $newStatusInstall,
                'reviews_install'  => $reviewsInstallFilename ?? $installationOrder?->reviews_install,
                'agency_name'      => $resolvedAgencyName,
                'agency_phone'     => $resolvedAgencyPhone,
                'agency_id'        => $resolvedAgencyId,
                'type'             => $request->type ?? $sourceOrder?->type ?? 'donhang',
                'zone'             => $sourceOrder?->zone ?? $installationOrder?->zone ?? '',
                'created_at'       => $sourceOrder?->created_at 
                    ?? $sourceWarrantyRequest?->Ngaytao 
                    ?? $installationOrder?->created_at 
                    ?? now(),
                'successed_at'     => $successedAt,
                'dispatched_at'    => $dispatchedAt,
                'paid_at'          => $paidAt,
                'agency_at'        => $agencyAt
            ];
            
            // Tạo hoặc cập nhật installation_orders (DUY NHẤT bảng được phép sửa)
            try {
                $installationOrder = InstallationOrder::updateOrCreate(
                    [
                        'order_code' => $orderCode,
                        'product'    => $productName ?? ($request->product ?? ''),
                    ],
                    $installationOrderData
                );
                
                // Ghi log thay đổi CTV nếu có thay đổi
                if ($oldCollaboratorId != ($isCollaboratorInstall ? $ctvId : null)) {
                    $this->logCollaboratorChange($oldCollaboratorId, ($isCollaboratorInstall ? $ctvId : null), $orderCode);
                }
                
                // Ghi log thay đổi trạng thái nếu có thay đổi
                if (isset($oldStatus) && $oldStatus != $newStatusInstall) {
                    $this->logStatusChange($orderCode, $oldStatus, $newStatusInstall, $action);
                }

                // Cập nhật trạng thái request_agency nếu có
                // LOGIC: Đồng bộ trạng thái request_agency với status_install khi điều phối
                if ($requestAgency && $newStatusInstall >= 1) {
                    $oldRequestAgencyStatus = $requestAgency->status;
                    $newRequestAgencyStatus = match($newStatusInstall) {
                        1 => RequestAgency::STATUS_DA_DIEU_PHOI,
                        2 => RequestAgency::STATUS_HOAN_THANH,
                        3 => RequestAgency::STATUS_DA_THANH_TOAN,
                        default => $requestAgency->status
                    };
                    
                    if ($newRequestAgencyStatus != $oldRequestAgencyStatus) {
                        $requestAgency->status = $newRequestAgencyStatus;
                        $requestAgency->assigned_to = session('user', 'system');
                        $requestAgency->save();
                        
                    }
                }
                
            } catch (\Exception $e) {
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thành công!'
            ]);
        } catch (\Exception $e) {
            // Log lỗi chi tiết để debug
            Log::error('Lỗi khi cập nhật installation order: ' . $e->getMessage(), [
                'request_data' => $request->except(['_token', 'installreview']),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật: ' . $e->getMessage()
            ], 500);
        }
    }

    // Filter cộng tác viên
    public function Filter(Request $request)
    {
        $lstCollaborator = WarrantyCollaborator::when($request->province, function ($q) use ($request) {
                $q->where('province_id', $request->province);
            })
            ->when($request->district, function ($q) use ($request) {
                $q->where('district_id', $request->district);
            })
            ->when($request->ward, function ($q) use ($request) {
                $q->where('ward_id', $request->ward);
            })
            ->orderBy('full_name')
            ->get();

        $html = view('collaboratorinstall.tablecollaborator', compact('lstCollaborator'))->render();
        return response()->json(['html' => $html]);
    }

    /**
     * Cập nhật thông tin khách hàng (tên, SĐT, địa chỉ)
     * Lưu vào bảng installation_orders để không thay đổi dữ liệu gốc trong orders
     */
    public function UpdateDetailCustomerAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_code'   => 'required|string',
            'full_name'    => 'nullable|string|max:80',
            'phone_number' => 'nullable|string|max:20',
            'address'      => 'nullable|string|max:150',
            'product'      => 'nullable|string|max:255',
            'province_id'  => 'nullable|integer',
            'district_id'  => 'nullable|integer',
            'ward_id'      => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $orderCode   = $request->input('order_code');
            $newAddress  = $request->input('address', '');
            $newName     = $request->input('full_name');
            $newPhone    = $request->input('phone_number');
            $product     = $request->input('product');
            $provinceId  = $request->input('province_id');
            $districtId  = $request->input('district_id');
            $wardId      = $request->input('ward_id');

            // Ghi log để debug dữ liệu nhận được từ frontend
            Log::info('UpdateDetailCustomerAddress payload', [
                'raw_request' => $request->all(),
                'order_code'  => $orderCode,
                'full_name'   => $newName,
                'phone'       => $newPhone,
                'address'     => $newAddress,
                'product'     => $product,
                'province_id' => $provinceId,
                'district_id' => $districtId,
                'ward_id'     => $wardId,
            ]);

            // Tìm installation_order hiện có
            $installationOrder = InstallationOrder::where('order_code', $orderCode)
                ->when($product, function ($q) use ($product) {
                    $q->where('product', $product);
                })
                ->first();

            if ($installationOrder) {
                // Nếu đã tồn tại, CHỈ cập nhật vào installation_orders (KHÔNG động vào orders)
                if (!is_null($newAddress)) {
                    $installationOrder->address = $newAddress;
                }
                if (!is_null($newName)) {
                    $installationOrder->full_name = $newName;
                }
                if (!is_null($newPhone)) {
                    $installationOrder->phone_number = $newPhone;
                }
                if (!is_null($provinceId)) {
                    $installationOrder->province_id = $provinceId;
                }
                if (!is_null($districtId)) {
                    $installationOrder->district_id = $districtId;
                }
                if (!is_null($wardId)) {
                    $installationOrder->ward_id = $wardId;
                }
                $installationOrder->save();
            } else {
                // Nếu chưa tồn tại, tìm dữ liệu từ orders hoặc warranty_requests để tạo mới
                // Tìm order theo order_code2 hoặc order_code1
                $order = Order::where('order_code2', $orderCode)
                    ->orWhere('order_code1', $orderCode)
                    ->first();
                $warrantyRequest = null;
                
                if (!$order) {
                    $warrantyRequest = WarrantyRequest::where('serial_number', $orderCode)->first();
                }

                if (!$order && !$warrantyRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy đơn hàng trong hệ thống'
                    ], 404);
                }

                // Chuẩn bị dữ liệu để tạo mới installation_order
                $data = [
                    'order_code' => $orderCode,
                    'address'    => $newAddress,
                ];

                if ($order) {
                    // Lấy từ orders (không sửa bảng orders, chỉ đọc dữ liệu)
                    $data['full_name'] = $order->customer_name;
                    $data['phone_number'] = $order->customer_phone;
                    $data['province_id'] = $order->province;
                    $data['district_id'] = $order->district;
                    $data['ward_id'] = $order->wards;
                    $data['agency_name'] = $order->agency_name ?? '';
                    $data['agency_phone'] = $order->agency_phone ?? '';
                    $data['zone'] = $order->zone;
                    $data['type'] = $order->type ?? 'donhang';
                    $data['created_at'] = $order->created_at;
                    // Nếu chưa có product mà request gửi lên có, thì dùng luôn
                    if ($product) {
                        $data['product'] = $product;
                    }
                } elseif ($warrantyRequest) {
                    // Lấy từ warranty_requests
                    $data['full_name'] = $warrantyRequest->full_name;
                    $data['phone_number'] = $warrantyRequest->phone_number;
                    $data['province_id'] = $warrantyRequest->province_id;
                    $data['district_id'] = $warrantyRequest->district_id;
                    $data['ward_id'] = $warrantyRequest->ward_id;
                    $data['agency_name'] = $warrantyRequest->agency_name ?? '';
                    $data['agency_phone'] = $warrantyRequest->agency_phone ?? '';
                    $data['product'] = $warrantyRequest->product;
                    $data['type'] = 'baohanh';
                    $data['created_at'] = $warrantyRequest->Ngaytao;
                }

                // Nếu có tên/SĐT mới từ request, ghi đè vào data trước khi tạo
                if (!is_null($newName)) {
                    $data['full_name'] = $newName;
                }
                if (!is_null($newPhone)) {
                    $data['phone_number'] = $newPhone;
                }

                // Tạo mới installation_order với thông tin đã cập nhật
                $installationOrder = InstallationOrder::create($data);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin khách hàng thành công!',
                'address' => $newAddress,
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi cập nhật địa chỉ khách hàng: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật địa chỉ: ' . $e->getMessage()
            ], 500);
        }
    }
}