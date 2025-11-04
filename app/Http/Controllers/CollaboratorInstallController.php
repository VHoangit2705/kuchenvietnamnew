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
use App\Enum;

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
        $this->saveLogController = new SaveLogController();
        $this->ensureAgencyCollaboratorExists();
    }

    /**
     * Đảm bảo CTV flag "Đại lý lắp đặt" với ID = 1 luôn tồn tại
     * Đây chỉ là một flag để đánh dấu, không phải thông tin thật
     */
    private function ensureAgencyCollaboratorExists()
    {
        try {
            $agencyCtv = WarrantyCollaborator::find(Enum::AGENCY_INSTALL_FLAG_ID);
            if (!$agencyCtv) {
                WarrantyCollaborator::create([
                    'id' => Enum::AGENCY_INSTALL_FLAG_ID,
                    'full_name' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'phone' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'province' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'district' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'ward' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'address' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'sotaikhoan' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'chinhanh' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'cccd' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi tạo CTV flag Đại lý lắp đặt: ' . $e->getMessage());
        }
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
                // Kiểm tra xem dữ liệu hiện tại có phải là CTV thật không (không phải flag "Đại lý lắp đặt")
                $isRealCtv = $existingHistory->new_collaborator_id && 
                            !Enum::isAgencyInstallFlag($existingHistory->new_collaborator_id) && 
                            $existingHistory->new_full_name != Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                
                if ($isRealCtv) {
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
                } else {
                    // Nếu đã là "Đại lý lắp đặt" rồi, không cần đẩy dữ liệu vào old_*
                    // Chỉ cần giữ nguyên dữ liệu cũ trong old_*
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
                    // Nếu không có CTV mới (chuyển về đại lý) - chỉ set flag
                    $existingHistory->new_collaborator_id = Enum::AGENCY_INSTALL_FLAG_ID;
                    $existingHistory->new_full_name = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_phone = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_province = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_province_id = null;
                    $existingHistory->new_district = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_district_id = null;
                    $existingHistory->new_ward = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_ward_id = null;
                    $existingHistory->new_address = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_sotaikhoan = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_chinhanh = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_cccd = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_ngaycap = null;
                }

                $existingHistory->action_type = 'update';
                $existingHistory->edited_by = session('user', 'system');
                $existingHistory->edited_at = now();
                
                $oldName = $oldCollaborator ? $oldCollaborator->full_name : Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                $newName = $newCollaborator ? $newCollaborator->full_name : Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
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

                $oldName = $oldCollaborator ? $oldCollaborator->full_name : Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                $newName = $newCollaborator ? $newCollaborator->full_name : Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
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
        $tab = $request->get('tab', 'donhang');
        $view = session('brand') === 'hurom' ? 3 : 1;

        // Lấy query builder cho tab
        $mainQuery = $this->buildTabQuery($tab, $view);

        // Áp dụng filters
        $mainQuery = $this->applyTabFilters($mainQuery, $tab, $request);

        // Phân trang + sắp xếp
        if ($tab === 'dieuphoibaohanh') {
            $data = $mainQuery->orderByDesc('Ngaytao')->orderByDesc('id')->paginate(50)->withQueryString();
        } elseif (in_array($tab, ['donhang', 'dieuphoidonhangle'])) {
            $data = $mainQuery->orderByDesc('orders.created_at')->orderByDesc('order_products.id')->paginate(50)->withQueryString();
        } else {
            $data = $mainQuery->orderByDesc('created_at')->orderByDesc('id')->paginate(50)->withQueryString();
        }

        $html = view('collaboratorinstall.tablecontent', compact('data'))->render();

        // Chỉ trả về bảng dữ liệu; header (counts) sẽ được gọi riêng qua endpoint counts để giảm query
        return response()->json([
            'table' => $html,
        ]);
    }

    /**
     * Build query cho từng tab
     */
    private function buildTabQuery($tab, $view)
    {
        // Định nghĩa closure trả về query cho từng tab
        $queryBuilders = [
            'donhang' => function () use ($view) {
                return OrderProduct::with('order')
                    ->join('products as p', function($join) {
                        $join->on('order_products.product_name', '=', 'p.product_name');
                    })
                    ->leftJoin('orders', 'order_products.order_id', '=', 'orders.id')
                    ->where('p.view', $view)
                    ->select('order_products.*')
                    ->where('order_products.install', 1)
                    ->whereHas('order', function ($q) {
                        $q->where('order_code2', 'not like', 'KU%')
                            ->where(function ($sub) {
                                // Chỉ hiển thị các đơn hàng chưa điều phối
                                $sub->whereNull('status_install')
                                    ->orWhere('status_install', 0);
                            })
                            // Chỉ lấy đơn thực sự chưa có CTV gán
                            ->whereNull('collaborator_id');
                    })
                    ->orderByDesc('orders.created_at');
            },
            'dieuphoidonhangle' => function () use ($view) {
                return OrderProduct::with('order')
                    ->join('products as p', function($join) {
                        $join->on('order_products.product_name', '=', 'p.product_name');
                    })
                    ->leftJoin('orders', 'order_products.order_id', '=', 'orders.id')
                    ->where('p.view', $view)
                    ->select('order_products.*')
                    ->where('order_products.install', 1)
                    ->whereHas('order', function ($q) {
                        $q->where(function ($sub) {
                                $sub->whereNull('status_install')
                                    ->orWhere('status_install', 0);
                            })
                            ->whereNull('collaborator_id')
                            ->whereIn('type', [
                                'warehouse_branch',
                                'warehouse_ghtk',
                                'warehouse_viettel'
                            ]);
                    })
                    ->orderByDesc('orders.created_at');
            },
            'dieuphoibaohanh' => function () use ($view) {
                return WarrantyRequest::where('type', 'agent_home')
                    ->where('view', $view);
            },
            'dadieuphoi' => function () use ($view) {
                return InstallationOrder::join('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where('p.view', $view)
                    ->select('installation_orders.*')
                    ->where('status_install', 1)
                    ->whereNotNull('collaborator_id')
                    ->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
            },
            'dailylapdat' => function () use ($view) {
                return InstallationOrder::join('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where('p.view', $view)
                    ->select('installation_orders.*')
                    ->where('status_install', 1)
                    ->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
            },
            'dahoanthanh' => function () use ($view) {
                return InstallationOrder::join('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where('p.view', $view)
                    ->select('installation_orders.*')
                    ->where('status_install', 2);
            },
            'dathanhtoan' => function () use ($view) {
                return InstallationOrder::join('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where('p.view', $view)
                    ->select('installation_orders.*')
                    ->where('status_install', 3);
            },
        ];
        
        return ($queryBuilders[$tab] ?? $queryBuilders['donhang'])();
    }

    /**
     * Áp dụng filter động cho query
     */
    private function applyTabFilters($query, $tab, Request $request)
    {
        $tungay = $request->input('tungay');
        $denngay = $request->input('denngay');
        $madon = $request->input('madon');
        $sanpham = $request->input('sanpham');
        $trangthai = $request->input('trangthai');
        $phanloai = $request->input('phanloai');
        $customer_name = $request->input('customer_name');
        $customer_phone = $request->input('customer_phone');
        $agency_phone = $request->input('agency_phone');
        $agency_name = $request->input('agency_name');

        // Filter chung cho các tab dựa trên OrderProduct
        // Sử dụng điều kiện trực tiếp trên bảng orders đã join thay vì whereHas để tối ưu hiệu năng
        if (in_array($tab, ['donhang', 'dieuphoidonhangle'])) {
            $query->when($madon, function ($q) use ($madon) {
                $q->where('orders.order_code2', 'like', "%$madon%");
            })
            ->when($sanpham, function ($q) use ($sanpham) {
                // Chỉ định rõ table prefix để tránh xung đột khi có join nhiều bảng
                $q->where('order_products.product_name', 'like', "%$sanpham%");
            })
            ->when($tungay && !empty($tungay), function ($q) use ($tungay) {
                // Sử dụng trực tiếp trên bảng orders đã join thay vì whereHas để tối ưu hiệu năng
                $q->whereDate('orders.created_at', '>=', $tungay);
            })
            ->when($denngay && !empty($denngay), function ($q) use ($denngay) {
                // Sử dụng trực tiếp trên bảng orders đã join thay vì whereHas để tối ưu hiệu năng
                $q->whereDate('orders.created_at', '<=', $denngay);
            })
            ->when($trangthai, function ($q) use ($trangthai) {
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
            ->when($phanloai, function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where('orders.collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('orders.collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name, function ($q) use ($customer_name) {
                $q->where('orders.customer_name', 'like', "%$customer_name%");
            })
            ->when($customer_phone, function ($q) use ($customer_phone) {
                $q->where('orders.customer_phone', 'like', "%$customer_phone%");
            })
            ->when($agency_phone, function ($q) use ($agency_phone) {
                $q->where('orders.agency_phone', 'like', "%$agency_phone%");
            })
            ->when($agency_name, function ($q) use ($agency_name) {
                $q->where('orders.agency_name', 'like', "%$agency_name%");
            });
        }
        // Filter cho WarrantyRequest (dieuphoibaohanh)
        elseif ($tab === 'dieuphoibaohanh') {
            $query->when($madon, fn($q) => $q->where('serial_number', 'like', "%$madon%"))
                ->when($sanpham, fn($q) => $q->where('product', 'like', "%$sanpham%"))
                ->when($tungay && !empty($tungay), fn($q) => $q->whereDate('Ngaytao', '>=', $tungay))
                ->when($denngay && !empty($denngay), function($q) use ($denngay) {
                    $q->whereDate('Ngaytao', '<=', $denngay);
                })
                ->when($trangthai, function ($q) use ($trangthai) {
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
                ->when($phanloai, function ($q) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        $q->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                    } elseif ($phanloai === 'agency') {
                        $q->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                    }
                })
                ->when($customer_name, fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
                ->when($customer_phone, fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
                ->when($agency_name, fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
                ->when($agency_phone, fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"));
        }
        // Filter cho InstallationOrder (các tab còn lại)
        else {
            $query->when($madon, fn($q) => $q->where('order_code', 'like', "%$madon%"))
                ->when($sanpham, fn($q) => $q->where('product', 'like', "%$sanpham%"))
                ->when($tungay && !empty($tungay), fn($q) => $q->whereDate('created_at', '>=', $tungay))
                ->when($denngay && !empty($denngay), function($q) use ($denngay) {
                    $q->whereDate('created_at', '<=', $denngay);
                })
                ->when($trangthai, function ($q) use ($trangthai) {
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
                ->when($phanloai, function ($q) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        $q->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                    } elseif ($phanloai === 'agency') {
                        $q->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                    }
                })
                ->when($customer_name, fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
                ->when($customer_phone, fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
                ->when($agency_name, fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
                ->when($agency_phone, fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"));
        }

        return $query;
    }

    public function Details(Request $request)
    {
        if ($request->type == 'donhang') {
            $data = OrderProduct::with('order')->findOrFail($request->id);
            $provinceId = $data->order->province ?? null;
            $districtId = $data->order->district ?? null;
            $wardId     = $data->order->wards ?? null;
            $agency_phone = $data->order->agency_phone;
        } else if ($request->type == 'baohanh') {
            $data = WarrantyRequest::findOrFail($request->id);
            $provinceId = $data->province_id ?? null;
            $districtId = $data->district_id ?? null;
            $wardId     = $data->ward_id ?? null;
            $agency_phone = $data->agency_phone;
        } else {
            $data = InstallationOrder::findOrFail($request->id);
            $provinceId = $data->province_id ?? null;
            $districtId = $data->district_id ?? null;
            $wardId     = $data->ward_id ?? null;
            $agency_phone = $data->agency_phone;
        }
        $agency = Agency::where('phone', $agency_phone)->first();
        $provinces = Province::orderBy('name')->get();
        
        $provinceName = $provinceId ? Province::find($provinceId)?->name : null;
        $districtName = $districtId ? District::find($districtId)?->name : null;
        $wardName     = $wardId ? Wards::find($wardId)?->name : null;
        $fullAddress = implode(', ', array_filter([$wardName, $districtName, $provinceName]));
        
        $lstCollaborator = WarrantyCollaborator::query()
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

        return view('collaboratorinstall.details', compact('data', 'lstCollaborator', 'provinces', 'agency', 'fullAddress'));
    }

    public function Update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'installcost' => 'nullable|numeric|min:0',
            'ctv_id' => 'nullable',
            'successed_at' => 'nullable',
            'installreview' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $model = match ($request->type) {
                'donhang'  => Order::findOrFail($request->id),
                'baohanh'  => WarrantyRequest::findOrFail($request->id),
                default    => InstallationOrder::findOrFail($request->id),
            };

            // Kiểm tra trạng thái đơn hàng
            $isPaidOrder = ($model->status_install === 3);
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
            $oldCollaboratorId = $model->collaborator_id;
            $oldStatus = $model->status_install;
            
            // Xử lý thay đổi trạng thái
            if (!$isPaidOrder) {
                // Chỉ thay đổi trạng thái nếu không phải đơn hàng đã thanh toán
                switch ($action) {
                    case 'update':
                        $model->status_install = 1;
                        break;

                    case 'complete':
                        $model->status_install = 2;
                        break;

                    case 'payment':
                        $model->status_install = 3;
                        break;
                }
            } else {
                // Nếu là đơn hàng đã thanh toán, chỉ cho phép cập nhật thông tin
                // KHÔNG thay đổi trạng thái (giữ nguyên status_install = 3)
                // vẫn cho phép cập nhật các thông tin khác
            }
            
            $model->collaborator_id = $request->ctv_id;
            $model->install_cost    = $request->installcost;
            $model->successed_at    = $request->successed_at;

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

                $model->reviews_install = $filename;
            }
            $model->save();

            // Ghi log thay đổi CTV nếu có thay đổi
            $orderCode = $model->order_code2 ?? $model->serial_number ?? $model->order_code;
            if (!empty($orderCode) && $oldCollaboratorId != $request->ctv_id) {
                $this->logCollaboratorChange($oldCollaboratorId, $request->ctv_id, $orderCode);
            }
            
            // Ghi log thay đổi trạng thái nếu có thay đổi
            if (!empty($orderCode) && isset($oldStatus) && $oldStatus != $model->status_install) {
                $this->logStatusChange($orderCode, $oldStatus, $model->status_install, $action);
            }

            try {
                // Chỉ thực hiện updateOrCreate nếu có order_code
                $orderCode = $model->order_code2 ?? $model->serial_number ?? $model->order_code;
                if (!empty($orderCode)) {
                    InstallationOrder::updateOrCreate(
                        [
                            'order_code' => $orderCode,
                        ],
                        [
                            'full_name'        => $model->customer_name ?? $model->full_name,
                            'phone_number'     => $model->customer_phone ?? $model->phone_number,
                            'address'          => $model->customer_address ?? $model->address,
                            'product'          => $request->product,
                            'province_id'      => $model->province ?? $model->province_id,
                            'district_id'      => $model->district ?? $model->district_id,
                            'ward_id'          => $model->wards ?? $model->ward_id,
                            'collaborator_id'  => $model->collaborator_id,
                            'install_cost'     => $model->install_cost,
                            'status_install'   => $model->status_install,
                            'reviews_install'  => $model->reviews_install,
                            'agency_name'      => $model->agency_name ?? '',
                            'agency_phone'     => $model->agency_phone ?? '',
                            'type'             => $request->type,
                            'zone'             => $model->zone,
                            'created_at'       => $model->created_at ?? $model->Ngaytao ?? null,
                            'successed_at'     => $model->successed_at
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error("InstallationOrder updateOrCreate failed", [
                    'error' => $e->getMessage(),
                    'model_id' => $model->id,
                    'order_code' => $orderCode ?? 'empty'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
}